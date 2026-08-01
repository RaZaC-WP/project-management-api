<?php

namespace App\Tests\Controller\Api;

class LoginTest extends ApiWebTestCase
{
    public function testLoginWithValidCredentialsReturnsUsableToken(): void
    {
        $this->createUserWithPassword('login-ok@test.com', 'Aabc123');

        $this->jsonRequest('POST', '/api/login_check', [
            'email' => 'login-ok@test.com',
            'password' => 'Aabc123',
        ]);

        $this->assertResponseIsSuccessful();

        $data = $this->decodeResponse();
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);

        // The token isn't just present, it actually has to work against a
        // protected endpoint — this closes the loop end-to-end.
        $this->client->request(
            'GET',
            '/api/projects',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $data['token']]
        );

        $this->assertResponseIsSuccessful();
    }

    public function testLoginWithWrongPasswordReturns401(): void
    {
        $this->createUserWithPassword('login-wrongpass@test.com', 'Aabc123');

        $this->jsonRequest('POST', '/api/login_check', [
            'email' => 'login-wrongpass@test.com',
            'password' => 'not-the-right-password',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginWithUnknownEmailReturns401(): void
    {
        $this->jsonRequest('POST', '/api/login_check', [
            'email' => 'does-not-exist@test.com',
            'password' => 'whatever',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }
}