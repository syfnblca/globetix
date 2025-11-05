<?php
include 'includes/db_connect.php';

if(isset($_GET['id']) && isset($_GET['aksi'])){
    $id = intval($_GET['id']);
    $aksi = $_GET['aksi'];

    if($aksi == 'setuju'){
        // Get booking_id from refund
        $query = mysqli_query($conn, "SELECT booking_id FROM refund WHERE refund_id=$id");
        $row = mysqli_fetch_assoc($query);
        $booking_id = $row['booking_id'];

        // Update refund status
        $sql1 = "UPDATE refund SET status_refund='disetujui', tanggal_selesai=NULL WHERE refund_id=$id";

        // Update booking status to cancelled
        $sql2 = "UPDATE booking SET status_booking='cancelled' WHERE booking_id=$booking_id";

        // Release seats
        $sql3 = "UPDATE kursi k JOIN booking_seat bs ON k.seat_id = bs.seat_id SET k.status = 'tersedia', k.locked_until = NULL WHERE bs.booking_id = $booking_id";

        // Execute all updates
        $success = mysqli_query($conn, $sql1) && mysqli_query($conn, $sql2) && mysqli_query($conn, $sql3);
    } elseif($aksi == 'tolak'){
        $sql = "UPDATE refund 
                SET status_refund='ditolak', tanggal_selesai=NOW() 
                WHERE refund_id=$id";
        $success = mysqli_query($conn,$sql);
    } elseif($aksi == 'selesai'){
        $sql = "UPDATE refund 
                SET status_refund='selesai', tanggal_selesai=NOW() 
                WHERE refund_id=$id";
        $success = mysqli_query($conn,$sql);
    }

    if($success){
        header("Location: refund.php?msg=success");
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    header("Location: refund.php");
    exit;
}
