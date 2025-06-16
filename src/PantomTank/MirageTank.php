<?
namespace api;

use Imagick;
use ImagickPixel;

class MirageTank
{
	private Imagick $sourceX;
	private Imagick $sourceY;
	public function __construct(Imagick $sourceX, Imagick $sourceY)
	{
		$this->sourceX = $sourceX;
		$this->sourceY = $sourceY;
	}

	public function build()
	{

		// 加载并调整源图像大小
		$imgA = ($this->sourceX);
		$imgB = ($this->sourceY);

		// 处理图像A：去饱和 -> 增加50%亮度 -> 反相
		$imgA = $this->desaturate($imgA);
		// $imgA->writeimage(DIR . "/debug_A1.png");
		$imgA = $this->adjustLightness($imgA, 0.5);
		// $imgA->writeimage(DIR . "/debug_A2.png");
		$imgA = $this->invert($imgA);
		// $imgA->writeimage(DIR . "/debug_A3.png");
		// 处理图像B：去饱和 -> 减少50%亮度
		$imgB = $this->desaturate($imgB);
		// $imgB->writeimage(DIR . "/debug_B1.png");
		$imgB = $this->adjustLightness($imgB, -0.5);
		// $imgB->writeimage(DIR . "/debug_B2.png");
		// 执行线性减淡混合
		$linearDodged = $this->linearDodgeBlend($imgA, $imgB);
		// $linearDodged->writeimage(DIR . "/debug_C.png");
		// 执行划分混合
		$divided = $this->divideBlend($linearDodged, $imgB);
		// $divided->writeimage(DIR . "/debug_D.png");
		// 合并结果：划分混合作为颜色，线性减淡作为Alpha通道
		$result = $this->addMask($divided, $linearDodged);
		// return 1;
		return $result;
	}

	private function desaturate(Imagick $image)
	{
		$pixels = $image->exportImagePixels(0, 0, $image->getImageWidth(), $image->getImageHeight(), "RGB", Imagick::PIXEL_CHAR);
		$count = count($pixels);
		$desaturated = [];

		for ($i = 0; $i < $count; $i += 3) {
			$r = $pixels[$i];
			$g = $pixels[$i + 1];
			$b = $pixels[$i + 2];
			$max = max($r, $g, $b);
			$min = min($r, $g, $b);
			$value = ($max + $min) / 2;
			$desaturated[] = $value;
			$desaturated[] = $value;
			$desaturated[] = $value;
			unset($r, $g, $b, $max, $min, $value);
		}
		$result = new Imagick();
		$result->newImage($image->getImageWidth(), $image->getImageHeight(), new ImagickPixel('black'));
		$result->importImagePixels(0, 0, $image->getImageWidth(), $image->getImageHeight(), "RGB", Imagick::PIXEL_CHAR, $desaturated);
		// $image->destroy();
		return $result;
	}

	private function adjustLightness(Imagick $image, $ratio)
	{
		$iterator = $image->getPixelIterator();
		foreach ($iterator as $row => $pixels) {
			foreach ($pixels as $column => $pixel) {
				$color = $pixel->getColor();
				$value = $color['r']; // 因为是灰度图，RGB值相同
				
				if ($ratio > 0) {
					$value = $value * (1 - $ratio) + 255 * $ratio;
				} else {
					$value = $value * (1 + $ratio);
				}
				
				$value = max(0, min(255, round($value)));
				$pixel->setColor("rgb($value,$value,$value)");
				unset($value, $color);
			}
			$iterator->syncIterator();
		}
		return $image;
	}

	private function invert(Imagick $image)
	{
		$image->negateImage(false);
		return $image;
	}

	private function linearDodgeBlend(Imagick $imgX, Imagick $imgY)
	{
		$width = $imgX->getImageWidth();
		$height = $imgX->getImageHeight();
		$result = new Imagick();
		$result->newImage($width, $height, new ImagickPixel('black'));
		$result->setImageType(Imagick::IMGTYPE_GRAYSCALE);

		$pixelsX = $imgX->exportImagePixels(0, 0, $width, $height, "R", Imagick::PIXEL_CHAR);
		$pixelsY = $imgY->exportImagePixels(0, 0, $width, $height, "R", Imagick::PIXEL_CHAR);
		$blended = [];

		for ($i = 0; $i < count($pixelsX); $i++) {
			$sum = $pixelsX[$i] + $pixelsY[$i];
			$blended[] = min(255, $sum);
		}

		$result->importImagePixels(0, 0, $width, $height, "R", Imagick::PIXEL_CHAR, $blended);
		// $imgX->destroy();
		// $imgY->destroy();
		return $result;
	}

	private function divideBlend(Imagick $imgX, Imagick $imgY)
	{
		$width = $imgX->getImageWidth();
		$height = $imgX->getImageHeight();
		$result = new Imagick();
		$result->newImage($width, $height, new ImagickPixel('black'));
		$result->setImageType(Imagick::IMGTYPE_GRAYSCALE);

		$pixelsX = $imgX->exportImagePixels(0, 0, $width, $height, "R", Imagick::PIXEL_CHAR);
		$pixelsY = $imgY->exportImagePixels(0, 0, $width, $height, "R", Imagick::PIXEL_CHAR);
		$blended = [];

		for ($i = 0; $i < count($pixelsX); $i++) {
			$x = $pixelsX[$i];
			$y = $pixelsY[$i];

			if ($x == 0) {
				$color = ($y != 0) ? 255 : 0;
			} elseif ($x == 255) {
				$color = $y;
			} elseif ($x == $y) {
				$color = 255;
			} else {
				$color = ($y / $x) * 255;
			}

			$blended[] = max(0, min(255, round($color)));
			unset($color, $x, $y);
		}

		$result->importImagePixels(0, 0, $width, $height, "R", Imagick::PIXEL_CHAR, $blended);
		// $imgX->destroy();
		// $imgY->destroy();
		return $result;
	}
	private function addMask(Imagick $imgX, Imagick $imgY): Imagick
	{
		$width = $imgX->getImageWidth();
		$height = $imgX->getImageHeight();
		
		$result = new Imagick();
		$result->newImage($width, $height, new ImagickPixel('transparent'));
		$result->setImageFormat('png');
		
		// 获取所有像素数据
		$pixelsX = $imgX->exportImagePixels(0, 0, $width, $height, "R", Imagick::PIXEL_CHAR);
		$pixelsY = $imgY->exportImagePixels(0, 0, $width, $height, "R", Imagick::PIXEL_CHAR);
		$combined = [];
		
		for ($i = 0; $i < count($pixelsX); $i++) {
			$gray = $pixelsX[$i];
			$alpha = $pixelsY[$i];
			$combined[] = $gray; // R
			$combined[] = $gray; // G
			$combined[] = $gray; // B
			$combined[] = $alpha; // A
			unset($alpha, $gray);
		}
		
		$result->importImagePixels(0, 0, $width, $height, "RGBA", Imagick::PIXEL_CHAR, $combined);
		// $imgX->destroy();
		// $imgY->destroy();
		return $result;
	}

}
