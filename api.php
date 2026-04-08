<?php

// ================= IMPORTANT =================
ini_set('memory_limit', '512M');
header("Content-Type: application/json");

// ================= FILE CHECK =================
if (!isset($_FILES['pdf'])) {
    echo json_encode(["success" => false, "msg" => "No file uploaded"]);
    exit;
}

if ($_FILES['pdf']['type'] !== 'application/pdf') {
    echo json_encode(["success" => false, "msg" => "Only PDF allowed"]);
    exit;
}

// ================= UPLOAD =================
$file = $_FILES['pdf']['tmp_name'];
$filename = uniqid() . ".pdf";
move_uploaded_file($file, $filename);

// ================= TEXT EXTRACT =================
exec("pdftotext $filename output.txt");

// ================= IMAGE EXTRACT =================
// low resolution (RAM safe)
exec("pdftoppm -png -r 100 $filename img");

// ================= READ TEXT =================
$text = file_get_contents("output.txt");

// clean text
$text = preg_replace('/\s+/', ' ', $text);
$text = trim($text);

// ================= FUNCTION =================
function getValue($text, $start, $endKeys = []) {
    $pattern = '/' . preg_quote($start, '/') . '\s+(.*?)\s+(' . implode('|', array_map(function($k){
        return preg_quote($k, '/');
    }, $endKeys)) . ')/i';

    if (preg_match($pattern, $text, $match)) {
        return trim($match[1]);
    }
    return null;
}

function clean($v) {
    return $v ? trim(preg_replace('/\s+/', ' ', $v)) : null;
}

// ================= DATA PARSE =================
$data = [
    "nid" => getValue($text, "National ID", ["Pin"]),
    "pin" => getValue($text, "Pin", ["Status"]),
    "status" => getValue($text, "Status", ["Afis Status"]),
    "afis_status" => getValue($text, "Afis Status", ["Lock Flag"]),
    "lock_flag" => getValue($text, "Lock Flag", ["Voter No"]),
    "voter_no" => getValue($text, "Voter No", ["Form No"]),
    "form_no" => getValue($text, "Form No", ["Sl No"]),
    "sl_no" => getValue($text, "Sl No", ["Tag"]),
    "tag" => getValue($text, "Tag", ["Name(Bangla)"]),

    "nameBangla" => getValue($text, "Name(Bangla)", ["Name(English)"]),
    "nameEnglish" => getValue($text, "Name(English)", ["Date of Birth"]),
    "dateOfBirth" => getValue($text, "Date of Birth", ["Birth Place"]),
    "birthPlace" => getValue($text, "Birth Place", ["Birth Registration"]),

    "birth_registration_no" => getValue($text, "Birth Registration", ["Father Name"]),
    "fatherName" => getValue($text, "Father Name", ["Mother Name"]),
    "motherName" => getValue($text, "Mother Name", ["Spouse Name"]),
    "spouseName" => getValue($text, "Spouse Name", ["Gender"]),

    "gender" => getValue($text, "Gender", ["Marital"]),
    "marital_status" => getValue($text, "Marital", ["Occupation"]),
    "occupation" => getValue($text, "Occupation", ["Disability"]),

    "religion" => getValue($text, "Religion", ["Religion Other"]),
    "education" => getValue($text, "Education", ["Education Other"]),
    "mobile" => getValue($text, "Mobile", ["Email"]),
];

$data = array_map('clean', $data);

// ================= ADDRESS =================
$present = [
    "division" => getValue($text, "Present Address Division", ["District"]),
    "district" => getValue($text, "District", ["RMO"]),
    "upazila" => getValue($text, "Upozila", ["Union/Ward"]),
    "union" => getValue($text, "Union/Ward", ["Mouza/Moholla"]),
    "village" => getValue($text, "Village/Road", ["Home/Holding"]),
    "post_office" => getValue($text, "Post Office", ["Postal Code"]),
    "postal_code" => getValue($text, "Postal Code", ["Region"]),
    "region" => getValue($text, "Region", ["Permanent Address"]),
];

$permanent = [
    "division" => getValue($text, "Permanent Address Division", ["District"]),
    "district" => getValue($text, "District", ["RMO"]),
    "upazila" => getValue($text, "Upozila", ["Union/Ward"]),
    "union" => getValue($text, "Union/Ward", ["Mouza/Moholla"]),
    "village" => getValue($text, "Village/Road", ["Home/Holding"]),
    "post_office" => getValue($text, "Post Office", ["Postal Code"]),
    "postal_code" => getValue($text, "Postal Code", ["Region"]),
    "region" => getValue($text, "Region", ["Education"]),
];

// ================= IMAGE (NO CROP) =================
$images = glob("img-*.png");

$imageList = [];

foreach ($images as $img) {
    $imageList[] = base64_encode(file_get_contents($img));
}

// ================= RESPONSE =================
echo json_encode([
    "success" => true,
    "data" => [
        ...$data,
        "images" => $imageList, // সব image এখানে
        "address" => "Auto generated",
        "present_address" => $present,
        "permanent_address" => $permanent,
        "additional_info" => []
    ]
]);

// ================= CLEANUP =================
@unlink($filename);
@unlink("output.txt");

foreach ($images as $img) {
    @unlink($img);
}
