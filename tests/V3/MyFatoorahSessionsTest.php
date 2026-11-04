<?php

namespace MyFatoorah\Test\V3;

use MyFatoorah\Test\MyFatoorahTestCase;
use MyFatoorah\Library\V3\MyFatoorahSessions;

class MyFatoorahSessionsTest extends MyFatoorahTestCase
{
    //-----------------------------------------------------------------------------------------------------------------------------------------
    public function testCreateAndGetSessionDetails(): void
    {
        $curlData = [
            'Order' => [
                'Amount'      => 10,
                'CurrencyIso' => 'KWD',
            ],
        ];

        foreach ($this->keys as $config) {
            try {
                $mfObj = new MyFatoorahSessions($config);
                $data  = $mfObj->createSession($curlData);

                $this->assertIsObject($data);
                $this->assertObjectHasProperty('SessionId', $data);

                $details = $mfObj->getSessionDetails($data->SessionId);

                $this->assertIsObject($details);
                $this->assertObjectHasProperty('SessionExpiry', $details);
            } catch (\Exception $ex) {
                $this->assertEquals($config['exception'], $ex->getMessage(), $config['message']);
            }
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}
