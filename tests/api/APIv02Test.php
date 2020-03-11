<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class APIv02Test extends TestCase {
	private $http;

	protected function setUp() : void {
		$this->http = new \GuzzleHttp\Client([
			'base_uri' => 'http://localhost:8080/index.php/apps/notes/api/v0.2/',
			'auth' => ['test', 'test'],
			'http_errors' => false,
		]);
	}

	private function checkResponse(
		\GuzzleHttp\Psr7\Response $response,
		$message,
		$statusExp,
		$contentTypeExp = 'application/json; charset=utf-8'
	) {
		$this->assertEquals($statusExp, $response->getStatusCode(), $message.': Response status code');
		$headers = $response->getHeaders();
		$this->assertTrue(array_key_exists('Content-Type', $headers), $message.': Response content-type exists');
		$this->assertEquals(
			$contentTypeExp,
			$response->getHeaders()['Content-Type'][0],
			$message.': Response content type'
		);
	}

	private function checkReferenceNotes(array $refNotes, $message) : void {
		$messagePrefix = 'Check reference notes '.$message;
		$response = $this->http->request('GET', 'notes');
		$this->checkResponse($response, $messagePrefix, 200);
		$notes = json_decode($response->getBody()->getContents());
		$notesMap = self::getNotesIdMap($notes);
		$this->assertEquals(count($refNotes), count($notes), $messagePrefix.': Number of notes');
		foreach($refNotes as $refNote) {
			$this->checkReferenceNote($refNote, $notesMap, $messagePrefix);
		}
	}

	private function checkReferenceNote(object $refNote, array $notes, $messagePrefix) : void {
		$this->assertTrue(array_key_exists($refNote->id, $notes), $messagePrefix.': Reference note '.$refNote->title.' exists');
		$note = $notes[$refNote->id];
		foreach(get_object_vars($refNote) as $key => $val) {
			$this->assertTrue(property_exists($note, $key), $messagePrefix.': Note has property '.$key.' (reference note '.$refNote->title.')');
			$this->assertEquals($refNote->$key, $note->$key, $messagePrefix.': Property '.$key.' (reference note '.$refNote->title.')');
		}
	}

	private static function getNotesIdMap(array $notes) : array {
		$map = [];
		foreach($notes as $note) {
			$map[$note->id] = $note;
		}
		return $map;
	}

	private function createNote(object $note, string $title) {
		$response = $this->http->request('POST', 'notes');
		$this->checkResponse($response, 'Create '.$title, 200);
		return json_decode($response->getBody()->getContents());

	}

	public function testCheckForReferenceNotes() : array {
		$response = $this->http->request('GET', 'notes');
		$this->checkResponse($response, 'Get existing notes', 200);
		$notes = json_decode($response->getBody()->getContents());
		if(count($notes) == 0) {
			// TODO move this to bootstrap file and switch to direct save in filesystem
			$notes[] = $this->createNote((object)[
				'content' => "First test note\nThis is some demo text.",
			], 'First test note');
			$notes[] = $this->createNote((object)[
				'content' => "Second test note\nThis is again some demo text.",
				'category' => 'Test',
				'modified' => mktime(1, 1, 1997),
				'favorite' => true,
			], 'Second test note');
		}
		return $notes;
	}

	/** @depends testCheckForReferenceNotes */
	public function testGetNotes(array $refNotes) : void {
		$this->checkReferenceNotes($refNotes, 'before');
		$response = $this->http->request('GET', 'notes');
		$this->checkResponse($response, 'Get notes', 200);
		// TODO test example notes
	}

	/** @depends testCheckForReferenceNotes */
	public function testGetNonExistingNoteFails(array $refNotes) : void {
		$response = $this->http->request('GET', 'notes/1');
		$this->assertEquals(404, $response->getStatusCode());
	}

	/** @depends testCheckForReferenceNotes */
	public function testCreateNote(array $refNotes) : void {
		// $this->checkReferenceNotes($refNotes, 'before');
		// TODO create note
		// TODO checkReferenceNotes with created note
		// TODO delete note
		// $this->checkReferenceNotes($refNotes, 'after');
	}

	/** @depends testCheckForReferenceNotes */
	public function testUpdateNote(array $refNotes) : void {
		// $this->checkReferenceNotes($refNotes, 'before');
		// TODO update note
		// TODO checkReferenceNotes with updated note
		// TODO update note (undo changes)
		// $this->checkReferenceNotes($refNotes, 'after');
	}
}

