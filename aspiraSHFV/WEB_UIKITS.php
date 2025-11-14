<?php
session_start();
include 'db_connection.php';

if (!isset($_GET['roomID'])) {
    die("❌ Error: Room ID not found!");
}

$roomID = $_GET['roomID'];
$session = null;
$isGroup = false;

if (strpos($roomID, 'room_') === 0) {
    // Private session
    $sql = "SELECT time, date FROM sessions WHERE room_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $roomID);
} elseif (strpos($roomID, 'group_') === 0) {
    // Group session
    $isGroup = true;
    $groupId = (int) str_replace('group_', '', $roomID);
    $sql = "SELECT session_time AS time, session_date AS date 
            FROM grp_sessions WHERE group_session_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $groupId);
} 

$stmt->execute();
$result = $stmt->get_result();
$session = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$session) {
    die("❌ Session not found or already ended");
}




date_default_timezone_set('Asia/Riyadh');
$session_start_time = strtotime($session['date'] . " " . $session['time']);
$session_end_time = $session_start_time + 60; // 45 mins duration
$current_time = time();
$time_remaining = max(0, $session_end_time - $current_time);

$userRole = $_SESSION['user_role'] ; // fallback if not set
?>

<html>
<head>
  <style>
    #root { width: 100vw; height: 100vh; }
  </style>
</head>
<body>
  <div id="root"></div>
</body>

<script src="https://unpkg.com/@zegocloud/zego-uikit-prebuilt/zego-uikit-prebuilt.js"></script>

<script>
window.onload = function () {
    const roomID = "<?php echo $roomID; ?>";
    const userID = Math.floor(Math.random() * 10000) + "";
    const userName = "user" + userID;
    const appID = 574673020;
    const serverSecret = "8c0e00f2ca5661e06c53bd0e879ad038";

    const kitToken = ZegoUIKitPrebuilt.generateKitTokenForTest(appID, serverSecret, roomID, userID, userName);

    const zp = ZegoUIKitPrebuilt.create(kitToken);
    zp.joinRoom({
        container: document.querySelector("#root"),
        sharedLinks: [{
            name: 'Session Link',
            url: window.location.href
        }],
        scenario: {
            mode: ZegoUIKitPrebuilt.VideoConference,
        },
        turnOnMicrophoneWhenJoining: true,
        turnOnCameraWhenJoining: true,
        showMyCameraToggleButton: true,
        showMyMicrophoneToggleButton: true,
        showAudioVideoSettingsButton: true,
        showScreenSharingButton: true,
        showTextChat: true,
        showUserList: true,
        maxUsers: 10,
        layout: "Auto",
        showLayoutButton: false,
        onLeaveRoom: () => {
            const role = "<?php echo $userRole; ?>";
            if (role === "mentor") {
                window.location.href = "MentorUpcomingSession.php";
            } else {
                window.location.href = "menteeUpcomingSession.php";
            }
        }
    });

    // Countdown to end
    const timeRemaining = <?php echo $time_remaining * 1000; ?>;
    if (timeRemaining > 0) {
        setTimeout(function () {
            alert("⏰ Session time ended! Redirecting...");
            fetch('update_session_status.php?roomID=' + roomID)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const role = "<?php echo $userRole; ?>";
                        if (role === "mentor") {
                            window.location.href = "mentor_past_sessions.php";
                        } else {
                            window.location.href = "mentee_past_sessions.php";
                        }
                    } else {
                        console.error("❌ Error updating status:", data.error);
                    }
                });
        }, timeRemaining);
    } else {
        alert("⛔️ Session already ended!");
        window.location.href = "<?php echo $userRole === 'mentor' ? 'mentor_past_sessions.php' : 'past_sessions.php'; ?>";
    }
}
</script>
</html>

