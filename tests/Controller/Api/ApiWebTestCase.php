<?php

namespace App\Tests\Controller\Api;

use App\Entity\Employee;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Base class for API functional tests.
 *
 * Every test runs inside a DB transaction that is rolled back in tearDown(),
 * so tests never leak data into one another and don't need manual cleanup.
 * This relies on `use_savepoints: true` in config/packages/doctrine.yaml,
 * which lets Doctrine's own internal flush() transactions nest safely
 * inside this outer transaction as savepoints instead of real commits.
 *
 * Kernel reboot between requests is disabled: by default KernelBrowser
 * rebuilds the container (and therefore opens a new DB connection) before
 * every request after the first one, which would make it "lose" the open
 * transaction and any entities (like the JWT-authenticated user) created
 * through $this->entityManager. Tests that only fire a single request are
 * not affected, but any test issuing two or more requests needs this.
 */
abstract class ApiWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->client->disableReboot();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->rollBack();
        $this->entityManager->close();

        parent::tearDown();
    }

    /**
     * Persists a user and returns an "Authorization: Bearer <jwt>" header
     * ready to be passed to KernelBrowser::request().
     */
    protected function authHeaders(array $roles = ['ROLE_USER']): array
    {
        $user = new User();
        $user->setEmail(sprintf('user-%s@test.com', uniqid()));
        $user->setRoles($roles);
        $user->setPassword('not-used-in-tests');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        /** @var JWTTokenManagerInterface $jwtManager */
        $jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);
        $token = $jwtManager->create($user);

        return ['HTTP_AUTHORIZATION' => 'Bearer ' . $token];
    }

    /**
     * Persists a user with a real, properly hashed password — unlike
     * authHeaders(), which bypasses the login flow entirely. Use this when
     * the test needs to exercise POST /api/login_check itself.
     */
    protected function createUserWithPassword(
        string $email,
        string $plainPassword,
        array $roles = ['ROLE_USER']
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function createProject(
        string $name = 'Test project',
        ?string $startDate = '2026-01-01',
        ?string $endDate = null
    ): Project {
        $project = new Project();
        $project->setName($name);
        $project->setStartDate(new \DateTimeImmutable($startDate));

        if ($endDate !== null) {
            $project->setEndDate(new \DateTimeImmutable($endDate));
        }

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }

    protected function createEmployee(
        string $fullName = 'Jane Doe',
        ?string $email = null,
        string $position = 'Developer'
    ): Employee {
        $employee = new Employee();
        $employee->setFullName($fullName);
        $employee->setEmail($email ?? sprintf('employee-%s@test.com', uniqid()));
        $employee->setPosition($position);

        $this->entityManager->persist($employee);
        $this->entityManager->flush();

        return $employee;
    }

    protected function jsonRequest(string $method, string $uri, array $payload = [], array $headers = []): void
    {
        $this->client->request(
            $method,
            $uri,
            server: array_merge(['CONTENT_TYPE' => 'application/json'], $headers),
            content: json_encode($payload)
        );
    }

    protected function decodeResponse(): array
    {
        return json_decode(
            $this->client->getResponse()->getContent(),
            true
        );
    }
}