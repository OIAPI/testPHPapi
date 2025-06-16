<?
namespace api;

use Imagick;

require_once __DIR__ . '/MirageTank.php';

class PantomTank {
    private Imagick $imageA;
	private Imagick $imageB;
	private int $max = 512;
	public function __construct(array $Imagicks)
	{
		$this->imageA = $Imagicks[0];
		$this->imageB = $Imagicks[1];
		$this->resize();
	}
	private function resize() {
		list($w, $h) = $this->scaleImage($this->imageA->getimagewidth(), $this->imageA->getImageheight(), $this->max);
		$this->imageA->resizeImage($w, $h, Imagick::FILTER_LANCZOS, 1);
		$this->imageB->resizeImage($w, $h, Imagick::FILTER_LANCZOS, 1);
	}
	public function save(string $outputPath): void
	{
		$this->output()->writeImage($outputPath);
	}
	public function output() {
		return (new MirageTank($this->imageA, $this->imageB))->build();
	}
	private function scaleImage($originalWidth, $originalHeight, $targetWidth) {
		// 计算缩放比例
		$scaleRatio = $targetWidth / $originalWidth;
		// 如果缩放后的宽度小于 targetWidth 像素，则保持原样
		if ($originalWidth < $targetWidth) {
			$targetWidth = $originalWidth;
			$newHeight = $originalHeight;
		} else {
			// 计算新的高度
			$newHeight = $originalHeight * $scaleRatio;
		}
	
		// 返回新的宽度和高度
		return array(intval($targetWidth), intval($newHeight));
	}
}
$a = new Imagick('./3babe323db0be3927569084b0c03d888.JPEG');
$b = new Imagick('./6c5658b5dea255c75defb0efeedd7060.gif');
$PantomTank = new PantomTank([$a, $b]);
$output = $PantomTank->output();
$output->writeImage('./output.png');
$output->destroy();
/**
 * 使用方法大概就是这样但是应该是不能开盖即用的
 */