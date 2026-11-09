<?php
header('Content-Type: application/json');

// Kuhaon ang data nga gipasa gikan sa Javascript
$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->total)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// PAYMONGO SETUP
$secret_key = getenv('PAYMONGO_SECRET_KEY') ?: '';
if ($secret_key === '') {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Payment configuration is missing']);
    exit;
} // Imong tinuod nga Test Key!
$amount_in_cents = intval($data->total * 100); // Ang PayMongo nagbasa og sentimo (₱50.00 = 5000)
$description = "Yokoso Cafe Order - " . date('Y-m-d H:i:s');

// PAYMONGO API PAYLOAD
$payload = [
    'data' => [
        'attributes' => [
            'amount' => $amount_in_cents,
            'description' => $description,
            'remarks' => 'Yokoso POS'
        ]
    ]
];

$ch = curl_init('https://api.paymongo.com/v1/links');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

// KINI ANG BAG-O NGA DUHA KA LINYA PARA DILI MA-BLOCK SA LOCALHOST:
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'accept: application/json',
    'Authorization: Basic ' . base64_encode($secret_key . ':')
]);

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo json_encode(['success' => false, 'message' => 'cURL Error: ' . $err]);
} else {
    $result = json_decode($response, true);
    if (isset($result['data']['attributes']['checkout_url'])) {
        // I-send pabalik sa Javascript ang Checkout URL nga gihatag sa PayMongo!
        echo json_encode([
            'success' => true, 
            'checkout_url' => $result['data']['attributes']['checkout_url'],
            'reference_number' => $result['data']['attributes']['reference_number']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'PayMongo Error', 'details' => $result]);
    }
}
?>