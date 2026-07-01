<?php
use PHPUnit\Framework\TestCase;

class SampleTest extends TestCase
{
    /**
     * Kiểm thử phép toán cơ bản (Đảm bảo PHPUnit hoạt động)
     */
    public function testBasicMath()
    {
        $this->assertEquals(4, 2 + 2, "2+2 phải bằng 4");
    }

    /**
     * Kiểm thử mô phỏng logic tạo mã OTP 6 số
     */
    public function testOtpGeneratorLength()
    {
        $otp = sprintf("%06d", mt_rand(0, 999999));
        $this->assertEquals(6, strlen($otp), "Mã OTP phải có đúng 6 ký tự");
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $otp, "Mã OTP chỉ được chứa chữ số");
    }

    /**
     * Kiểm thử mô phỏng logic tính tổng giỏ hàng
     */
    public function testCartTotalCalculation()
    {
        $cart = [
            ['price' => 150000, 'quantity' => 2],
            ['price' => 50000, 'quantity' => 1]
        ];

        $total = array_reduce($cart, function($sum, $item) {
            return $sum + ($item['price'] * $item['quantity']);
        }, 0);

        $this->assertEquals(350000, $total, "Tổng tiền giỏ hàng phải được tính chính xác");
    }
}
