<?php

header("Content-Type: application/json");

// check file
if (!isset($_FILES['pdf'])) {
    echo json_encode(["success" => false, "msg" => "No file uploaded"]);
    exit;
}

// validate PDF
if ($_FILES['pdf']['type'] !== 'application/pdf') {
    echo json_encode(["success" => false, "msg" => "Only PDF allowed"]);
    exit;
}

// upload file
$file = $_FILES['pdf']['tmp_name'];
$filename = uniqid() . ".pdf";

move_uploaded_file($file, $filename);

// extract text
exec("pdftotext $filename output.txt");

// extract images
exec("pdftoppm $filename img -png");

// read text
$text = file_get_contents("output.txt");

// clean text (VERY IMPORTANT)
$text = preg_replace('/\s+/', ' ', $text);
$text = trim($text);

// function to extract value
function getValue($text, $key) {
    preg_match('/' . preg_quote($key, '/') . '\s+(.+?)(?=\s+[A-Z][a-z]|\s+[A-Z]\(|$)/', $text, $match);
    return isset($match[1]) ? trim($match[1]) : null;
}

// extract data
$data = [
    "nid" => getValue($text, "National ID"),
    "pin" => getValue($text, "Pin"),
    "status" => getValue($text, "Status"),
    "afis_status" => getValue($text, "Afis Status"),
    "lock_flag" => getValue($text, "Lock Flag"),
    "voter_no" => getValue($text, "Voter No"),
    "form_no" => getValue($text, "Form No"),
    "sl_no" => getValue($text, "Sl No"),
    "tag" => getValue($text, "Tag"),
    "nameBangla" => getValue($text, "Name(Bangla)"),
    "nameEnglish" => getValue($text, "Name(English)"),
    "dateOfBirth" => getValue($text, "Date of Birth"),
    "birthPlace" => getValue($text, "Birth Place"),
    "birth_registration_no" => getValue($text, "Birth Registration"),
    "fatherName" => getValue($text, "Father Name"),
    "motherName" => getValue($text, "Mother Name"),
    "spouseName" => getValue($text, "Spouse Name"),
    "gender" => getValue($text, "Gender"),
    "marital_status" => getValue($text, "Marital"),
    "occupation" => getValue($text, "Occupation"),
    "religion" => getValue($text, "Religion"),
    "education" => getValue($text, "Education"),
    "mobile" => getValue($text, "Mobile"),
];

// image extract → base64
$images = glob("img-*.png");

$userIMG = null;
$signIMG = null;

if (isset($images[0])) {
    $userIMG = base64_encode(file_get_contents($images[0]));
}

if (isset($images[1])) {
    $signIMG = base64_encode(file_get_contents($images[1]));
}

// final response
$response = [
    "success" => true,
    "data" => [
        "nid" => $data["nid"],
        "pin" => $data["pin"],
        "status" => $data["status"],
        "afis_status" => $data["afis_status"],
        "lock_flag" => $data["lock_flag"],
        "voter_no" => $data["voter_no"],
        "form_no" => $data["form_no"],
        "sl_no" => $data["sl_no"],
        "tag" => $data["tag"],
        "nameBangla" => $data["nameBangla"],
        "nameEnglish" => $data["nameEnglish"],
        "dateOfBirth" => $data["dateOfBirth"],
        "birthPlace" => $data["birthPlace"],
        "birth_registration_no" => $data["birth_registration_no"],
        "fatherName" => $data["fatherName"],
        "motherName" => $data["motherName"],
        "spouseName" => $data["spouseName"],
        "gender" => $data["gender"],
        "marital_status" => $data["marital_status"],
        "occupation" => $data["occupation"],
        "religion" => $data["religion"],
        "education" => $data["education"],
        "mobile" => $data["mobile"],
        "userIMG" => $userIMG,
        "signIMG" => $signIMG,

        // simple address (optional)
        "address" => "Auto extract coming soon",

        "present_address" => [],
        "permanent_address" => [],
        "additional_info" => []
    ]
];

echo json_encode($response);

// cleanup
@unlink($filename);
@unlink("output.txt");

foreach (glob("img-*.png") as $img) {
    @unlink($img);
}
