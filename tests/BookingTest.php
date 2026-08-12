<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\TestDox;

final class BookingTest extends ApiTest
{
	#[TestDox('GET /booking debe responder 200 y devolver un listado de IDs [Importancia: Crítica]')]
	public function testGetBookingIds(): void
	{
		$response = $this->client()->get('booking');

		$this->recordHttpCall('GET', 'booking', $response, 200, 'Crítica', 'GET /booking - listado de IDs');

		$payload = json_decode((string) $response->getBody(), true);

		$this->assertIsArray($payload);
		$this->assertNotEmpty($payload);
		$this->assertIsArray($payload[0]);
		$this->assertArrayHasKey('bookingid', $payload[0]);
		$this->assertIsInt($payload[0]['bookingid']);
	}

	#[TestDox('GET /booking/{id} debe responder 200 y devolver los datos correctos del booking [Importancia: Crítica]')]
	public function testGetBooking(): void
	{
		[$bookingId, $createdPayload] = $this->createBooking();

		$response = $this->client()->get('booking/' . $bookingId);

		$this->recordHttpCall('GET', 'booking/' . $bookingId, $response, 200, 'Crítica', 'GET /booking/' . $bookingId);

		$payload = json_decode((string) $response->getBody(), true);

		$this->assertIsArray($payload);
		$this->recordFieldCheck('firstname', $createdPayload['firstname'], $payload['firstname'], 'Mayor');
		$this->recordFieldCheck('lastname', $createdPayload['lastname'], $payload['lastname'], 'Mayor');
		$this->recordFieldCheck('totalprice', $createdPayload['totalprice'], $payload['totalprice'], 'Mayor');
		$this->recordFieldCheck('depositpaid', $createdPayload['depositpaid'], $payload['depositpaid'], 'Menor');
		$this->recordFieldCheck('bookingdates.checkin', $createdPayload['bookingdates']['checkin'], $payload['bookingdates']['checkin'], 'Mayor');
		$this->recordFieldCheck('bookingdates.checkout', $createdPayload['bookingdates']['checkout'], $payload['bookingdates']['checkout'], 'Mayor');
		$this->recordFieldCheck('additionalneeds', $createdPayload['additionalneeds'], $payload['additionalneeds'], 'Menor');
	}

	#[TestDox('POST /booking debe crear un booking y devolver 200 con el ID generado [Importancia: Crítica]')]
	public function testCreateBooking(): void
	{
		[$bookingId, $createdPayload] = $this->createBooking();

		$this->assertGreaterThan(0, $bookingId);

		$response = $this->client()->get('booking/' . $bookingId);

		$this->recordHttpCall('GET', 'booking/' . $bookingId, $response, 200, 'Crítica', 'GET /booking/' . $bookingId . ' tras crearlo');

		$payload = json_decode((string) $response->getBody(), true);

		$this->assertIsArray($payload);
		$this->recordFieldCheck('firstname', $createdPayload['firstname'], $payload['firstname'], 'Mayor');
		$this->recordFieldCheck('lastname', $createdPayload['lastname'], $payload['lastname'], 'Mayor');
	}

	#[TestDox('PUT /booking/{id} debe actualizar todos los campos y responder 200 [Importancia: Crítica]')]
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

		$this->recordHttpCall('PUT', 'booking/' . $bookingId, $response, 200, 'Crítica', 'PUT /booking/' . $bookingId);

		$payload = json_decode((string) $response->getBody(), true);

		$this->assertIsArray($payload);
		$this->recordFieldCheck('firstname', $updatedPayload['firstname'], $payload['firstname'], 'Mayor');
		$this->recordFieldCheck('lastname', $updatedPayload['lastname'], $payload['lastname'], 'Mayor');
		$this->recordFieldCheck('totalprice', $updatedPayload['totalprice'], $payload['totalprice'], 'Mayor');
		$this->recordFieldCheck('depositpaid', $updatedPayload['depositpaid'], $payload['depositpaid'], 'Menor');
		$this->recordFieldCheck('bookingdates.checkin', $updatedPayload['bookingdates']['checkin'], $payload['bookingdates']['checkin'], 'Mayor');
		$this->recordFieldCheck('bookingdates.checkout', $updatedPayload['bookingdates']['checkout'], $payload['bookingdates']['checkout'], 'Mayor');
		$this->recordFieldCheck('additionalneeds', $updatedPayload['additionalneeds'], $payload['additionalneeds'], 'Menor');
	}

	#[TestDox('PATCH /booking/{id} debe actualizar campos parciales y responder 200 [Importancia: Mayor]')]
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

		$this->recordHttpCall('PATCH', 'booking/' . $bookingId, $response, 200, 'Mayor', 'PATCH /booking/' . $bookingId);

		$payload = json_decode((string) $response->getBody(), true);

		$this->assertIsArray($payload);
		$this->recordFieldCheck('firstname', $partialPayload['firstname'], $payload['firstname'], 'Mayor');
		$this->recordFieldCheck('additionalneeds', $partialPayload['additionalneeds'], $payload['additionalneeds'], 'Menor');
	}

	#[TestDox('DELETE /booking/{id} debe responder 201 y el booking debe dejar de existir (404) [Importancia: Crítica]')]
	public function testDeleteBooking(): void
	{
		[$bookingId] = $this->createBooking();

		$response = $this->client()->delete('booking/' . $bookingId, [
			'headers' => $this->authHeaders(),
		]);

		$this->recordHttpCall('DELETE', 'booking/' . $bookingId, $response, 201, 'Crítica', 'DELETE /booking/' . $bookingId);

		$deletedResponse = $this->client()->get('booking/' . $bookingId, [
			'http_errors' => false,
		]);

		$this->recordHttpCall('GET', 'booking/' . $bookingId, $deletedResponse, 404, 'Crítica', 'GET /booking/' . $bookingId . ' tras eliminarlo');
	}

	private function createBooking(): array
	{
		$payload = $this->bookingPayload();

		$response = $this->client()->post('booking', [
			'json' => $payload,
		]);

		$this->recordHttpCall('POST', 'booking', $response, 200, 'Bloqueante', 'POST /booking - setup previo al test');

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