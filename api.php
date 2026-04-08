<?php

header("Content-Type: application/json");

// check file
if (!isset($_FILES['pdf'])) {
    echo json_encode(["status" => false, "msg" => "No file uploaded"]);
    exit;
}

// validate file type
if ($_FILES['pdf']['type'] !== 'application/pdf') {
    echo json_encode(["status" => false, "msg" => "Only PDF allowed"]);
    exit;
}

// move uploaded file
$file = $_FILES['pdf']['tmp_name'];
$filename = uniqid() . ".pdf";

move_uploaded_file($file, $filename);

// extract text
exec("pdftotext $filename output.txt");

// extract images
exec("pdftoppm $filename img -png");

// get text content
$text = file_get_contents("output.txt");

// extract data using regex
preg_match('/National ID (\d+)/', $text, $nid);
preg_match('/Name\(English\) (.+)/', $text, $nameMatch);

// get images
$images = glob("img-*.png");

// base URL (⚠️ deploy হওয়ার পর এটা change করবে)
$base = "https://your-app.onrender.com/";

// full image URL
foreach ($images as &$img) {
    $img = $base . $img;
}

// response
$response = [
    "status" => true,
    "nid" => $nid[1] ?? null,
    "name" => $nameMatch[1] ?? null,
    "images" => $images
];

echo json_encode($response);

// cleanup (optional but recommended)
@unlink($filename);
@unlink("output.txt");
foreach (glob("img-*.png") as $img) {
    @unlink($img);
}
