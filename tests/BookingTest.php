<?php

declare(strict_types=1);

final class BookingTest extends ApiTest
{
	public function testGetBookingIds(): void
	{
		$response = $this->client()->get('booking');

		$this->assertSame(200, $response->getStatusCode());

		$payload = json_decode((string) $response->getBody(), true);

		$this->assertIsArray($payload);
		$this->assertNotEmpty($payload);
		$this->assertIsArray($payload[0]);
		$this->assertArrayHasKey('bookingid', $payload[0]);
		$this->assertIsInt($payload[0]['bookingid']);
	}

	public function testGetBooking(): void
	{
		[$bookingId, $createdPayload] = $this->createBooking();

		$response = $this->client()->get('booking/' . $bookingId);

		$this->assertSame(200, $response->getStatusCode());

		$payload = json_decode((string) $response->getBody(), true);

		$this->assertIsArray($payload);
		$this->assertSame($createdPayload['firstname'], $payload['firstname']);
		$this->assertSame($createdPayload['lastname'], $payload['lastname']);
		$this->assertSame($createdPayload['totalprice'], $payload['totalprice']);
		$this->assertSame($createdPayload['depositpaid'], $payload['depositpaid']);
		$this->assertSame($createdPayload['bookingdates']['checkin'], $payload['bookingdates']['checkin']);
		$this->assertSame($createdPayload['bookingdates']['checkout'], $payload['bookingdates']['checkout']);
		$this->assertSame($createdPayload['additionalneeds'], $payload['additionalneeds']);
	}

	public function testCreateBooking(): void
	{
		[$bookingId, $createdPayload] = $this->createBooking();

		$this->assertGreaterThan(0, $bookingId);

		$response = $this->client()->get('booking/' . $bookingId);

		$this->assertSame(200, $response->getStatusCode());

		$payload = json_decode((string) $response->getBody(), true);

		$this->assertIsArray($payload);
		$this->assertSame($createdPayload['firstname'], $payload['firstname']);
		$this->assertSame($createdPayload['lastname'], $payload['lastname']);
	}

	public function testUpdateBooking(): void
	{
		[$bookingId] = $this->createBooking();

		$updatedPayload = $this->bookingPayload([
			'firstname' => 'Updated',
			'lastname' => 'Guest',
			'totalprice' => 222,
			'depositpaid' => false,
			'bookingdates' => [
				'checkin' => '2026-08-10',
				'checkout' => '2026-08-20',
			],
			'additionalneeds' => 'Breakfast',
		]);

		$response = $this->client()->put('booking/' . $bookingId, [
			'headers' => $this->authHeaders(),
			'json' => $updatedPayload,
		]);

		$this->assertSame(200, $response->getStatusCode());

		$payload = json_decode((string) $response->getBody(), true);

		$this->assertIsArray($payload);
		$this->assertSame($updatedPayload['firstname'], $payload['firstname']);
		$this->assertSame($updatedPayload['lastname'], $payload['lastname']);
		$this->assertSame($updatedPayload['totalprice'], $payload['totalprice']);
		$this->assertSame($updatedPayload['depositpaid'], $payload['depositpaid']);
		$this->assertSame($updatedPayload['bookingdates']['checkin'], $payload['bookingdates']['checkin']);
		$this->assertSame($updatedPayload['bookingdates']['checkout'], $payload['bookingdates']['checkout']);
		$this->assertSame($updatedPayload['additionalneeds'], $payload['additionalneeds']);
	}

	public function testPartialUpdateBooking(): void
	{
		[$bookingId] = $this->createBooking();

		$partialPayload = [
			'firstname' => 'Partial',
			'additionalneeds' => 'Late Checkout',
		];

		$response = $this->client()->patch('booking/' . $bookingId, [
			'headers' => $this->authHeaders(),
			'json' => $partialPayload,
		]);

		$this->assertSame(200, $response->getStatusCode());

		$payload = json_decode((string) $response->getBody(), true);

		$this->assertIsArray($payload);
		$this->assertSame($partialPayload['firstname'], $payload['firstname']);
		$this->assertSame($partialPayload['additionalneeds'], $payload['additionalneeds']);
	}

	public function testDeleteBooking(): void
	{
		[$bookingId] = $this->createBooking();

		$response = $this->client()->delete('booking/' . $bookingId, [
			'headers' => $this->authHeaders(),
		]);

		$this->assertSame(201, $response->getStatusCode());

		$deletedResponse = $this->client()->get('booking/' . $bookingId, [
			'http_errors' => false,
		]);

		$this->assertSame(404, $deletedResponse->getStatusCode());
	}

	private function createBooking(): array
	{
		$payload = $this->bookingPayload();

		$response = $this->client()->post('booking', [
			'json' => $payload,
		]);

		$this->assertSame(200, $response->getStatusCode());

		$responsePayload = json_decode((string) $response->getBody(), true);

		$this->assertIsArray($responsePayload);
		$this->assertArrayHasKey('bookingid', $responsePayload);
		$this->assertIsInt($responsePayload['bookingid']);
		$this->assertArrayHasKey('booking', $responsePayload);

		return [(int) $responsePayload['bookingid'], $payload];
	}

	private function bookingPayload(array $overrides = []): array
	{
		$defaultPayload = [
			'firstname' => 'Test',
			'lastname' => 'User',
			'totalprice' => 111,
			'depositpaid' => true,
			'bookingdates' => [
				'checkin' => '2026-07-10',
				'checkout' => '2026-07-20',
			],
			'additionalneeds' => 'Lunch',
		];

		return array_replace_recursive($defaultPayload, $overrides);
	}
}

