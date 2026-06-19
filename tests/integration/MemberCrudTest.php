<?php

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class MemberCrudTest extends TestCase
{
    private static Client $client;
    private static array $formMeta;
    private ?int $createdId = null;

    public static function setUpBeforeClass(): void
    {
        self::$client = loginAsPersona('admin');
        $resp = self::$client->get('/api/member-form.php');
        self::$formMeta = json_decode((string)$resp->getBody(), true);
    }

    public static function tearDownAfterClass(): void
    {
        restoreAllPersonas();
    }

    protected function tearDown(): void
    {
        if ($this->createdId) {
            $con = testDb();
            mysqli_query($con, "DELETE FROM members WHERE id=" . intval($this->createdId));
            mysqli_query($con, "DELETE FROM role_member WHERE member_id=" . intval($this->createdId));
        }
    }

    public function testCreateMember(): void
    {
        $uid = uniqueId();
        $firstname = 'CrudT-' . $uid;
        $surname = 'CrudT-' . $uid;
        $displayname = 'CrudT ' . $uid;

        $resp = self::$client->post('/api/member-form.php', [
            'form_params' => [
                'firstname' => $firstname,
                'surname' => $surname,
                'displayname' => $displayname,
                'class' => self::$formMeta['classes'][0]['id'],
                'status' => self::$formMeta['statuses'][0]['id'],
                'email' => $uid . '@test-crud.com',
                'phone_mobile' => '021' . substr($uid, -8),
            ],
        ]);

        $this->assertEquals(200, $resp->getStatusCode());
        $body = json_decode((string)$resp->getBody(), true);
        $this->assertTrue($body['success'], 'create failed: ' . ($body['message'] ?? ''));
        $this->assertNotEmpty($body['member_id']);

        $this->createdId = intval($body['member_id']);

        assertRowExists(testDb(), 'members', [
            'id' => $this->createdId,
            'firstname' => $firstname,
            'surname' => $surname,
        ], 'Created member should exist in DB');
    }

    public function testEditMember(): void
    {
        // First create
        $uid = uniqueId();
        $firstname = 'EditT-' . $uid;
        $surname = 'EditT-' . $uid;

        $resp = self::$client->post('/api/member-form.php', [
            'form_params' => [
                'firstname' => $firstname,
                'surname' => $surname,
                'displayname' => 'EditT ' . $uid,
                'class' => self::$formMeta['classes'][0]['id'],
                'status' => self::$formMeta['statuses'][0]['id'],
                'email' => $uid . '@test-edit.com',
            ],
        ]);
        $body = json_decode((string)$resp->getBody(), true);
        $this->createdId = intval($body['member_id']);

        // Now edit
        $newSurname = 'Edited-' . $uid;
        $resp2 = self::$client->post('/api/member-form.php', [
            'form_params' => [
                'id' => $this->createdId,
                'firstname' => $firstname,
                'surname' => $newSurname,
                'displayname' => 'EditT ' . $uid,
                'class' => self::$formMeta['classes'][0]['id'],
                'status' => self::$formMeta['statuses'][0]['id'],
                'email' => $uid . '@test-edit.com',
            ],
        ]);

        $this->assertEquals(200, $resp2->getStatusCode());
        $body2 = json_decode((string)$resp2->getBody(), true);
        $this->assertTrue($body2['success'], 'edit failed: ' . ($body2['message'] ?? ''));
        $this->assertEquals($this->createdId, $body2['member_id']);

        assertRowExists(testDb(), 'members', [
            'id' => $this->createdId,
            'surname' => $newSurname,
        ], 'Edited surname should be updated in DB');
    }

    public function testSearchMemberReturnsResults(): void
    {
        // Create a member with a recognizable name
        $uid = uniqueId();
        $firstname = 'SearchMe-' . $uid;
        $surname = 'SearchMe-' . $uid;

        $resp = self::$client->post('/api/member-form.php', [
            'form_params' => [
                'firstname' => $firstname,
                'surname' => $surname,
                'displayname' => 'SearchMe ' . $uid,
                'class' => self::$formMeta['classes'][0]['id'],
                'status' => self::$formMeta['statuses'][0]['id'],
                'email' => $uid . '@test-search.com',
            ],
        ]);
        $body = json_decode((string)$resp->getBody(), true);
        $this->createdId = intval($body['member_id']);

        // Search by firstname
        $searchResp = self::$client->get('/api/member-search', [
            'query' => ['search' => $firstname],
        ]);
        $this->assertEquals(200, $searchResp->getStatusCode());
        $searchBody = json_decode((string)$searchResp->getBody(), true);
        $this->assertIsArray($searchBody);
        $ids = array_column($searchBody, 'id');
        $this->assertContains($this->createdId, $ids, 'Created member should appear in search results');
    }

    public function testCreateMemberRejectsMissingRequiredFields(): void
    {
        $resp = self::$client->post('/api/member-form.php', [
            'form_params' => [
                'firstname' => '',
                'surname' => '',
                'displayname' => '',
                'class' => 0,
                'status' => 0,
            ],
        ]);

        $body = json_decode((string)$resp->getBody(), true);
        $this->assertFalse($body['success'], 'Should reject empty required fields');
        $this->assertStringContainsString('required', strtolower($body['message'] ?? ''));
    }
}
