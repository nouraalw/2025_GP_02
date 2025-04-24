<?php
session_start();
include 'db_connection.php';

if (!isset($_GET['roomID'])) {
    die("Error : Room ID not found!");
}

$roomID = $_GET['roomID'];

//   وقت بدء الجلسة المحجوز من قاعدة البيانات
$sql = "SELECT time, date FROM sessions WHERE room_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $roomID);
$stmt->execute();
$result = $stmt->get_result();
$session = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$session) {
    die("Error,the sesstion not found");
}


date_default_timezone_set('Asia/Riyadh'); 
$session_start_time = strtotime($session['date'] . " " . $session['time']);
$session_end_time = $session_start_time + (45 * 60); // الجلسة مدتها 45 دقيقة
$current_time = time();
$time_remaining = max(0, $session_end_time - $current_time); 

?>

<html>

<head>
    <style>
        #root {
            width: 100vw;
            height: 100vh;
        }
    </style>
</head>

<body>
    <div id="root"></div>
</body>

<!-- تحميل مكتبة ZEGOCLOUD -->
<script src="https://unpkg.com/@zegocloud/zego-uikit-prebuilt/zego-uikit-prebuilt.js"></script>

<script>
window.onload = function () {
    function getUrlParams(url) {
        let urlStr = url.split('?')[1];
        const urlSearchParams = new URLSearchParams(urlStr);
        return Object.fromEntries(urlSearchParams.entries());
    }

    const roomID = getUrlParams(window.location.href)['roomID']; 
    if (!roomID) {
        alert("Error : Room ID not found");
        return;
    }

    const userID = Math.floor(Math.random() * 10000) + "";
    const userName = "user" + userID;
    const appID = 1039080135;
    const serverSecret = "a503edcb519639c8aba065f3e67e6ba2";
    
    const kitToken = ZegoUIKitPrebuilt.generateKitTokenForTest(appID, serverSecret, roomID, userID, userName);

    const zp = ZegoUIKitPrebuilt.create(kitToken);
    zp.joinRoom({
    container: document.querySelector("#root"),
    sharedLinks: [{
        name: 'Personal link',
        url: window.location.protocol + '//' + window.location.host  + window.location.pathname + '?roomID=' + roomID,
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
    maxUsers: 2,
    layout: "Auto",
    showLayoutButton: false,

    
    onLeaveRoom: () => {
        if (userRole === "mentor") {
            window.location.href = "MentorUpcomingSession.php";
        } else {
            window.location.href = "menteeUpcomingSession.php";
        }
    }
});

    
    let userRole = localStorage.getItem('userRole') || sessionStorage.getItem('userRole') || 'mentee'; 
    console.log("User Role:", userRole); 

    
    let timeRemaining = <?php echo $time_remaining * 1000; ?>; // **تحويله إلى ميلي ثانية**
    console.log(" Time remaining:", timeRemaining / 1000, "seconds");

    // التأكد من أن الجلسة تنتهي في الوقت المحدد
    if (timeRemaining > 0) {
        setTimeout(function () {
            alert(" The session time has ended! You will be redirected shortly.");


            // ✅ تحديث حالة الجلسة إلى "completed" في قاعدة البيانات
            fetch('update_session_status.php?roomID=' + roomID)
                .then(response => response.json())
                .then(data => {
                    console.log("Session Status Updated:", data);
                    if (data.success) {
                        
                        if (userRole === "mentor") {
                            window.location.href = "mentor_past_sessions.php"; // المنتور ينتقل إلى صفحة إضافة جلسة جديدة
                        } else {
                            window.location.href = "past_sessions.php"; // المنتي ينتقل إلى صفحة Past Sessions
                        }
                    } else {
                        console.error("❌Error updating the session:", data.error);
                    }
                })
                .catch(error => console.error('❌ Error while updating the session:', error));
        }, timeRemaining); // ✅ سيتم إنهاء الجلسة بناءً على الوقت المتبقي
    } else {
        console.log(" The session has already ended!");
        alert("️Session time is over!");
        window.location.href = (userRole === "mentor") ? "mentor_past_sessions.php" : "past_sessions.php";
    }
}
</script>

</html>
