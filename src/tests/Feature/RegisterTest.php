<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use App\Models\User;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    // 名前が入力されていない場合、バリデーションメッセージが表示される.
    public function test_name_is_required_for_registration()
    {
        $this->withoutExceptionHandling();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            $this->post('/register', [
                '_token' => csrf_token(),
                'name' => '',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $nameError = $e->errors()['name'][0];
            $this->assertEquals('お名前を入力してください', $nameError);
            throw $e;
        }
    }

    // メールアドレスが入力されていない場合、バリデーションメッセージが表示される.
    public function test_email_is_required_for_registration()
    {
        $this->withoutExceptionHandling();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            $this->post('/register', [
                '_token' => csrf_token(),
                'name' => 'テストユーザー',
                'email' => '',
                'password' => 'password123',
                'password_confirmation' => 'password123'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $emailError = $e->errors()['email'][0];
            $this->assertEquals('メールアドレスを入力してください', $emailError);
            throw $e;
        }
    }

    // パスワードが入力されていない場合、バリデーションメッセージが表示される.
    public function test_password_is_required_for_registration()
    {
        $this->withoutExceptionHandling();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            $this->post('/register', [
                '_token' => csrf_token(),
                'name' => 'テストユーザー',
                'email' => 'test@example.com',
                'password' => '',
                'password_confirmation' => 'password123'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $passwordError = $e->errors()['password'][0];
            $this->assertEquals('パスワードを入力してください', $passwordError);
            throw $e;
        }
    }

    // パスワードが7文字以内の場合、バリデーションメッセージが表示される.
    public function test_password_8characters_is_required_for_registration()
    {
        $this->withoutExceptionHandling();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            $this->post('/register', [
                '_token' => csrf_token(),
                'name' => 'テストユーザー',
                'email' => 'test@example.com',
                'password' => 'pass',
                'password_confirmation' => 'pass'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $passwordError = $e->errors()['password'][0];
            $this->assertEquals('パスワードは8文字以上で入力してください', $passwordError);
            throw $e;
        }
    }

    // パスワードと確認用パスワードが一致しない場合、バリデーションメッセージが表示される.
    public function test_password_confirmation_is_required_for_registration()
    {
        $this->withoutExceptionHandling();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            $this->post('/register', [
                '_token' => csrf_token(),
                'name' => 'テストユーザー',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password456'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $passwordError = $e->errors()['password_confirmation'][0];
            $this->assertEquals('パスワードと一致しません', $passwordError);
            throw $e;
        }
    }

    // 全ての項目が入力されている場合、会員情報が登録され、メール認証画面に遷移する
    public function test_register_new_user()
    {
        $this->withoutExceptionHandling();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        Notification::fake();

        $userData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);

        $response->assertRedirect('/email/verify');

        Notification::assertSentTo(
            User::where('email', 'test@example.com')->first(),
            \Illuminate\Auth\Notifications\VerifyEmail::class
        );
    }
}
