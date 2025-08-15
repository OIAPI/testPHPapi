<?php


class FiveElementsDressingGuide  {
    /**
     * 五行相生关系
     * @var array
     */
    private $generate = [
        '木' => '火',
        '火' => '土',
        '土' => '金',
        '金' => '水',
        '水' => '木'
    ];
    
    /**
     * 五行相克关系
     * @var array
     */
    private $restrain = [
        '木' => '土',
        '土' => '水',
        '水' => '火',
        '火' => '金',
        '金' => '木'
    ];
    
    /**
     * 五行对应的颜色（包含中文名称和16进制代码）
     * @var array 结构：['颜色名称' => '十六进制值']
     */
    private $elementColors = [
        '木' => [
            '绿色' => '#008000',
            '青色' => '#00FFFF',
            '碧色' => '#4A90E2'
        ],
        '火' => [
            '红色' => '#FF0000',
            '紫色' => '#800080',
            '粉色' => '#FFC0CB'
        ],
        '土' => [
            '黄色' => '#FFFF00',
            '棕色' => '#A52A2A',
            '卡其色' => '#F0E68C'
        ],
        '金' => [
            '白色' => '#FFFFFF',
            '金色' => '#FFD700',
            '银色' => '#C0C0C0'
        ],
        '水' => [
            '黑色' => '#000000',
            '蓝色' => '#0000FF',
            '灰色' => '#808080'
        ]
    ];
    public function main() {
    	$guide = $this->formatGuide($this->date);
    	if(count($guide) == 2) {
    		return $this->ret(1, $guide[0], $guide[1]);
    	}
    	return $guide;
    }

    /**
     * 验证日期格式是否为 Y-m-d
     * @param string $date 待验证的日期字符串
     * @return bool 验证通过返回true，否则返回false
     */
    private function validateDateFormat($date) {
        // 空值视为未指定日期（使用默认值）
        if (empty($date)) {
            return true;
        }
        
        // 使用正则表达式验证格式
        $pattern = '/^\d{4}-\d{2}-\d{2}$/';
        if (!preg_match($pattern, $date)) {
            throw new Exception("请输入正确的日期", -1);
            return false;
        }
        
        // 验证日期是否有效（避免2024-13-32这种无效日期）
        $dateParts = explode('-', $date);
        return checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0]);
    }
    
    /**
     * 获取指定日期的五行属性
     * @param string $date 日期格式 Y-m-d，默认为今天
     * @return string|false 五行属性：木、火、土、金、水；失败返回false
     */
    public function getElementOfDay($date = '') {
        // 验证日期格式
        if (!$this->validateDateFormat($date)) {
            throw new Exception("无效的日期格式：{$date}，请使用 Y-m-d 格式", -2);
            return false;
        }
        
        $date = $date ?: date('Y-m-d');
        $timestamp = strtotime($date);
        
        // 基于农历天干地支的简化计算（实际应用建议对接专业农历库）
        $dayOfYear = (int)date('z', $timestamp) + 1;
        $elementIndex = ($dayOfYear % 5);
        
        $elements = ['木', '火', '土', '金', '水'];
        return $elements[$elementIndex];
    }
    
    /**
     * 获取当日穿衣指南（包含16进制颜色）
     * @param string $date 日期格式 Y-m-d，默认为今天
     * @return array|false 包含推荐颜色及16进制值的指南；失败返回false
     */
    public function getDressingGuide($date = '') {
        // 先验证日期并获取五行属性
        $element = $this->getElementOfDay($date);
        if ($element === false) {
            throw new Exception("五行属性获取失败", -3);
        }
        
        // 相生颜色（大吉）
        $auspiciousElement = $this->generate[$element];
        $auspiciousColors = $this->elementColors[$auspiciousElement];
        
        // 同属性颜色（中吉）
        $neutralColors = $this->elementColors[$element];
        
        // 相克颜色（不宜）
        $inauspiciousElement = $this->restrain[$element];
        $inauspiciousColors = $this->elementColors[$inauspiciousElement];
        
        return [
            'date' => $date ?: date('Y-m-d'),
            'day_element' => $element,
            'best_colors' => $auspiciousColors,       // 格式：['颜色名' => '#十六进制']
            'good_colors' => $neutralColors,
            'avoid_colors' => $inauspiciousColors,
            'description' => "今日五行属{$element}，宜穿" . implode('、', array_keys($auspiciousColors)) . 
                            "系（相生）和" . implode('、', array_keys($neutralColors)) . 
                            "系（相助），避免" . implode('、', array_keys($inauspiciousColors)) . "系（相克）"
        ];
    }
    
    /**
     * 格式化输出指南（包含16进制颜色）
     * @param string $date 日期
     * @return string|false 格式化的指南文本；失败返回false
     */
    public function formatGuide($date = '') {
        $guide = $this->getDressingGuide($date);
        if ($guide === false) {
            throw new Exception("穿衣指南获取失败", -4);
            return false;
        }
        
        // 处理颜色文本（名称+16进制）
        $formatColors = function($colors) {
            $result = [];
            foreach ($colors as $name => $hex) {
                $result[] = "{$name}({$hex})";
            }
            return implode('、', $result);
        };
        
        return [sprintf(
            "%s 五行穿衣指南：\n" .
            "今日五行：%s\n" .
            "大吉颜色：%s\n" .
            "次吉颜色：%s\n" .
            "不宜颜色：%s\n" .
            "说明：%s",
            $guide['date'],
            $guide['day_element'],
            $formatColors($guide['best_colors']),
            $formatColors($guide['good_colors']),
            $formatColors($guide['avoid_colors']),
            $guide['description']
        ), $guide];
    }
    public function ret(int $code, string $msg, array | object $data = [], ?string $type = 'json') {

        return $type == 'json' ? json_encode([

            'code' => $code,
            'message' => $msg,
            'data' => $data
        ], 460) :
            $msg;
    }
    public function __get($key) {
        return isset($_REQUEST[$key]) ? $_REQUEST[$key] : null;
    }
}

print_r((new FiveElementsDressingGuide())->main());
