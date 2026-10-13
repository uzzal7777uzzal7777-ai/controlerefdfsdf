<?php
// Enable error logging for debugging if needed
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Bot Configuration
$token = "8655619720:AAH88_YaX-96yLTI5tuexJm6dbV5fGBatxI";
$admin_id = 6913254882;

// Exact MySQL Details (Screenshot 169 অনুсерে)
$db_host = "sql306.infinityfree.com"; 
$db_user = "if0_43159723";          
$db_pass = "vQv5hduQgg3NZ"; // এখানে আপনার ইনফিনিটিফ্রি অ্যাকাউন্টের পাসওয়ার্ড দিন
$db_name = "if0_43159723_botdb";       

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Auto Initialize Tables
$conn->query("CREATE TABLE IF NOT EXISTS users (user_id BIGINT PRIMARY KEY, name VARCHAR(255), username VARCHAR(255))");
$conn->query("CREATE TABLE IF NOT EXISTS channels (user_id BIGINT, channel VARCHAR(255), PRIMARY KEY(user_id, channel))");
$conn->query("CREATE TABLE IF NOT EXISTS user_states (user_id BIGINT PRIMARY KEY, state VARCHAR(255), data TEXT)");

$content = file_get_contents("php://input");
$update = json_decode($content, TRUE);

if (!$update) {
    echo "Bot is active and running successfully!";
    exit;
}

function apiRequest($method, $parameters = []) {
    global $token;
    $url = "https://api.telegram.org/bot" . $token . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

function getState($conn, $user_id) {
    $stmt = $conn->prepare("SELECT state, data FROM user_states WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res ? $res : ['state' => null, 'data' => null];
}

function setState($conn, $user_id, $state, $data = null) {
    $data_json = $data ? json_encode($data) : null;
    $stmt = $conn->prepare("REPLACE INTO user_states (user_id, state, data) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $state, $data_json);
    $stmt->execute();
    $stmt->close();
}

function clearState($conn, $user_id) {
    $stmt = $conn->prepare("DELETE FROM user_states WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

function getMainKeyboard($user_id, $admin_id) {
    $keyboard = [
        'keyboard' => [
            [["text" => "✨ Create Post"], ["text" => "📢 Add Channel"]],
            [["text" => "📁 My Channels"], ["text" => "🛟 Help & Support"]]
        ],
        'resize_keyboard' => true
    ];
    if ($user_id == $admin_id) {
        $keyboard['keyboard'][] = [["text" => "👑 Admin Panel"]];
    }
    return $keyboard;
}

if (isset($update["message"])) {
    $message = $update["message"];
    $chat_id = $message["chat"]["id"];
    $text = $message["text"] ?? "";
    $name = $message["from"]["first_name"] ?? "User";
    $username = $message["from"]["username"] ?? "None";

    if ($text == "/start") {
        clearState($conn, $chat_id);
        $stmt = $conn->prepare("INSERT IGNORE INTO users (user_id, name, username) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $chat_id, $name, $username);
        $stmt->execute();
        $stmt->close();

        apiRequest("sendMessage", [
            'chat_id' => $chat_id,
            'text' => "👋 <b>Welcome {$name} to Controller Bot!</b>\n\nManage your channels professionally.\n\n👇 Select an option from the menu below:",
            'parse_mode' => 'HTML',
            'reply_markup' => getMainKeyboard($chat_id, $admin_id)
        ]);
    }
}
?>
