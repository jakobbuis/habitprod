<?php

/*
 * Reminder fires an SMS message on the previously determined moment
 * stored in the planning phase (planning.php) in Redis.
 */
use Carbon\Carbon;
use Predis\Client as PredisClient;
use Twilio\Rest\Client as TwilioClient;

require __DIR__.'/bootstrap.php';

$twilio = new TwilioClient($config['twilio']['sip'], $config['twilio']['token']);
$redis = new PredisClient;

foreach ($config['clients'] as $client) {
    // get the desired moment to send the message
    $moment = new Carbon($redis->get($client['number']));
    $now = Carbon::now('Europe/Amsterdam');
    $now->second = 0;

    // Determine if it's time for a message
    if (!$moment->eq($now)) {
        writeLog('Not sending message to ' . $client['number']);
        continue;
    }

    // Send a message
    writeLog('Sending message to ' . $client['number']);
    $message = $client['messages'][array_rand($client['messages'])];
    $twilio->messages->create($client['number'], [
        'from' => $config['twilio']['from_number'],
        'body' => $message,
    ]);
}
