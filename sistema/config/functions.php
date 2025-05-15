<?php

require_once 'config.php';
require_once __DIR__ . '/../phpqrcode/qrlib.php';

#region Inventory
    #region Locations
function getLocations(){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT *
                                    FROM inventory_locations");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result;
}
    #endregion

    #region Display Inventory
function displayInventory(){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT * 
                                    FROM inventory");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result;
}

function checkInventoryAvailability($inventory_id){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT *
                                    FROM inventory_loans
                                    WHERE fk_inventory_id = ? AND `status` = 'Borrowed'");
    mysqli_stmt_bind_param($stmt, "i", $inventory_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) > 0){
        return false; 
    }

    return true;
}

function displayStorage(){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT *
                                    FROM inventory_locations");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result;
}
    #endregion

    #region Add Inventory
function addInventory($name, $location, $serial_number, $inventory_number, $description){
    global $conn;
    
    $sticker_path = generateSticker($name, $serial_number, $inventory_number);

    $stmt = mysqli_prepare($conn, "INSERT INTO inventory(`name`, fk_inventory_location_id, serial_number, 
                                                        inventory_number, `description`, sticker_path)
                                    VALUES(?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sissss", $name, $location, $serial_number, $inventory_number, $description, $sticker_path);
    mysqli_stmt_execute($stmt);
    $affected_rows = mysqli_stmt_affected_rows($stmt);

    if($affected_rows > 0){
        $_SESSION['success_message'] = "Inventorius pridėtas sėkmingai!";
        header("Location: inventory.php");
        exit();
    } else{
        $_SESSION['error_message'] = "Inventoriaus pridėti nepavyko! Bandykite dar kartą!";
        header("Location: inventory.php");
        exit();
    }
}

function generateSticker($name, $serial_number, $inventory_number){
    $path = __DIR__. '/../images/qr_codes/';
    $file_path = $path . clean($name) . "_" . clean($serial_number) . "_" . clean($inventory_number) . '.png';
    $file = clean($name) . "_" . clean($serial_number) . "_" . clean($inventory_number) . '.png';
    $_SESSION['generated_sticker'] = $file;

    $data = $name . "__" . $serial_number . "__" . $inventory_number;

    QRcode::png($data, $file_path, QR_ECLEVEL_Q, 256, 4);

    return $file;
}

function clean($string) {
    $string = str_replace(' ', '_', $string); // Replaces all spaces with hyphens.
 
    return preg_replace('/[^A-Za-z0-9\-]/', '-', $string); // Removes special chars.
}
    #endregion

    #region Edit Inventory
function getInventoryById($inventory_id){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT *
                                    FROM inventory
                                    WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $inventory_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return $row;
}

function deleteSticker($inventory_id){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT *
                                FROM inventory
                                WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $inventory_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    $path = __DIR__. '/../images/qr_codes/';
    $sticker_path = $path . clean($row['name']) . "_" . clean($row['serial_number']) . "_" . clean($row['inventory_number']) . '.png';
    if(!unlink($sticker_path)){
        return false;
    }

    return true;
}

function updateInventory($name, $location, $serial_number, $inventory_number, $description, $inventory_id){
    global $conn;

    if(deleteSticker($inventory_id)){
        $sticker_path = generateSticker($name, $serial_number, $inventory_number);

        $stmt = mysqli_prepare($conn, "UPDATE inventory
                                        SET `name`= ?, fk_inventory_location_id = ?, serial_number = ?, inventory_number = ?, `description` = ?, sticker_path = ?
                                        WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "sissssi", $name, $location, $serial_number, $inventory_number, $description, $sticker_path, $inventory_id);
        mysqli_stmt_execute($stmt);
        $affected_rows = mysqli_stmt_affected_rows($stmt);

        if($affected_rows > 0){
            $_SESSION['success_message'] = "Inventorius atnaujintas sėkmingai!";
            header("Location: inventory.php");
            exit();
        } else{
            $row = getInventoryById($inventory_id);

            if($row['name'] === $name && $row['fk_inventory_location_id'] === $location && $row['serial_number'] === $serial_number && 
            $row['inventory_number'] === $inventory_number && $row['description'] === $description){
                $_SESSION['error_message'] = "Įrašyti duomenys atitinka jau esamus duomenis!";
                return;
            }

            $_SESSION['error_message'] = "Inventoriaus atnaujinti nepavyko! Bandykite dar kartą!";
            return;
        }
    } else{
        $_SESSION['error_message'] = "Inventoriaus atnaujinti nepavyko! Bandykite dar kartą!";
        return;
    }
}
    #endregion

    #region Delete Inventory
function deleteInventory($inventory_id, $name, $serial_number, $inventory_number){
    global $conn;

    $stmt = mysqli_prepare($conn, "DELETE FROM inventory
                                    WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $inventory_id);
    mysqli_stmt_execute($stmt);
    $affected_rows = mysqli_stmt_affected_rows($stmt);

    if($affected_rows > 0){
        $_SESSION['success_message'] = "Inventoriaus įrašas ištrintas sėkmingai!";

        $path = __DIR__. '/../images/qr_codes/';
        $sticker_path = $path . clean($name) . "_" . clean($serial_number) . "_" . clean($inventory_number) . '.png';

        unlink($sticker_path);
        header("Location: inventory.php");
        exit();
    } else{
        $_SESSION['error_message'] = "Inventoriaus įrašo ištrinti nepavyko! Bandykite dar kartą!";
        exit();
    }
}
    #endregion

    #region Add Storage
function generateStorageSticker($name){
    $path = __DIR__. '/../images/qr_codes/';
    $file_path = $path . clean($name) . '.png';
    $file = clean($name) . '.png';
    $_SESSION['generated_sticker'] = $file;

    $data = $name;

    QRcode::png($data, $file_path, QR_ECLEVEL_Q, 256, 4);

    return $file;
}

function addStorage($name, $description, $lock_name, $lock_public_key, $lock_address){
    global $conn;

    if(($lock_name !== "" || $lock_public_key !== "" || $lock_address !== "") && ($lock_name === "" || $lock_public_key === "" || $lock_address === "")){
        $_SESSION['error_message'] = "Norint pridėti talpyklos elektroninio užrakto informaciją būtina užpildyti visus su elektroniniu užraktu susijusius laukus!";
        header("Location: add_storage.php");
        exit();
    }

    if(empty($lock_name)){
        $lock_name = NULL;
    }
    if(empty($lock_public_key)){
        $lock_public_key = NULL;
    }
    if(empty($lock_address)){
        $lock_address = NULL;
    }

    $stmt = mysqli_prepare($conn, "SELECT * 
                                    FROM inventory_locations
                                    WHERE `name` = ?");
    mysqli_stmt_bind_param($stmt, "s", $name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_free_result($stmt);

    if($affected_rows > 0){
        $_SESSION['error_message'] = "Talpykla su tokiu pavadinimu jau egzistuoja! Prašome pasirinkti kitą pavadinimą!";
        header("Location: add_storage.php");
        exit();
    }

    $sticker_path = generateStorageSticker($name);

    $stmt = mysqli_prepare($conn, "INSERT INTO inventory_locations(`name`, `description`, sticker_path, device_name, public_key, `address`)
                                    VALUES(?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssssss", $name, $description, $sticker_path, $lock_name, $lock_public_key, $lock_address);
    mysqli_stmt_execute($stmt);
    $affected_rows = mysqli_stmt_affected_rows($stmt);

    if($affected_rows > 0){
        $_SESSION['success_message'] = "Talpykla pridėta sėkmingai!";
        header("Location: inventory.php");
        exit();
    } else{
        $_SESSION['error_message'] = "Talpyklos pridėti nepavyko! Bandykite dar kartą!";
        header("Location: inventory.php");
        exit();
    }
}
    #endregion
    
    #region Edit Storage
function getStorageById($storage_id){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT *
                                    FROM inventory_locations
                                    WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $storage_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return $row;
}

function deleteStorageSticker($storage_id){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT *
                                FROM inventory_locations
                                WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $storage_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    $path = __DIR__. '/../images/qr_codes/';
    $sticker_path = $path . clean($row['name']) . '.png';
    if(!unlink($sticker_path)){
        return false;
    }

    return true;
}

function updateStorage($name, $description, $lock_name, $lock_public_key, $lock_address, $storage_id){
    global $conn;

    if(($lock_name !== "" || $lock_public_key !== "" || $lock_address !== "") && ($lock_name === "" || $lock_public_key === "" || $lock_address === "")){
        $_SESSION['error_message'] = "Norint atnaujinti talpyklos elektroninio užrakto informaciją būtina užpildyti visus su elektroniniu užraktu susijusius laukus!";
        return;
    }

    if(empty($lock_name)){
        $lock_name = NULL;
    }
    if(empty($lock_public_key)){
        $lock_public_key = NULL;
    }
    if(empty($lock_address)){
        $lock_address = NULL;
    }

    $row = getStorageById($storage_id);

    if($row['name'] !== $name){
        $stmt = mysqli_prepare($conn, "SELECT * 
                                        FROM inventory_locations
                                        WHERE `name` = ?");
        mysqli_stmt_bind_param($stmt, "s", $name);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $affected_rows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_free_result($stmt);

        if($affected_rows > 0){
            $_SESSION['error_message'] = "Talpykla su tokiu pavadinimu jau egzistuoja! Prašome pasirinkti kitą pavadinimą!";
            return;
        }
    }

    if(deleteStorageSticker($storage_id)){
        $sticker_path = generateStorageSticker($name);

        $stmt = mysqli_prepare($conn, "UPDATE inventory_locations
                                        SET `name`= ?, `description` = ?, sticker_path = ?, device_name = ?, public_key = ?, `address` = ?
                                        WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssssssi", $name, $description, $sticker_path, $lock_name, $lock_public_key, $lock_address, $storage_id);
        mysqli_stmt_execute($stmt);
        $affected_rows = mysqli_stmt_affected_rows($stmt);

        if($affected_rows > 0){
            $_SESSION['success_message'] = "Talpykla atnaujinta sėkmingai!";
            header("Location: inventory.php");
            exit();
        } else{
            if($row['name'] === $name && $row['description'] === $description && $row['device_name'] === $lock_name && $row['public_key'] === $lock_public_key && $row['address'] === $lock_address){
                $_SESSION['error_message'] = "Įrašyti duomenys atitinka jau esamus duomenis!";
                return;
            }

            $_SESSION['error_message'] = "Talpyklos atnaujinti nepavyko! Bandykite dar kartą!";
            return;
        }
    } else{
        $_SESSION['error_message'] = "Talpyklos atnaujinti nepavyko! Bandykite dar kartą!";
        return;
    }
}
    #endregion

    #region Delete Storage
function deleteStorage($storage_id, $name){
    global $conn;

    $stmt = mysqli_prepare($conn, "DELETE FROM inventory_locations
                                    WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $storage_id);
    mysqli_stmt_execute($stmt);
    $affected_rows = mysqli_stmt_affected_rows($stmt);

    if($affected_rows > 0){
        $_SESSION['success_message'] = "Talpyklos įrašas ištrintas sėkmingai!";

        $path = __DIR__. '/../images/qr_codes/';
        $sticker_path = $path . clean($name) . '.png';

        unlink($sticker_path);
        header("Location: inventory.php");
        exit();
    } else{
        $_SESSION['error_message'] = "Talpyklos įrašo ištrinti nepavyko! Bandykite dar kartą!";
        exit();
    }
}
    #endregion
    #endregion

#region Loans
    #region Display Loans
function display_loans(){
    global $conn;
    $user_id = $_SESSION['user_id'];

    $stmt = mysqli_prepare($conn, "SELECT inventory_loans.*, inventory.name
                                    FROM inventory_loans
                                    INNER JOIN inventory ON inventory_loans.fk_inventory_id = inventory.id
                                    WHERE fk_user_id = ? && `status` = 'Borrowed'");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result;
}
    #endregion

    #region Loan Actions
function selectInventoryByIdCodeParams($name, $serial_number, $inventory_number){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT *
                                        FROM inventory
                                        WHERE name = ? AND serial_number = ? AND inventory_number = ?");
    mysqli_stmt_bind_param($stmt, "sss", $name, $serial_number, $inventory_number);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return $row;
}

        #region Unlock Storage
function selectStorageByIdCodeParams($name){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT *
                                    FROM inventory_locations
                                    WHERE name = ?");
    mysqli_stmt_bind_param($stmt, "s", $name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return $row;
}

function unlockStorage($storage_id_code){
    $parsed = explode("__", $storage_id_code);

    if(count($parsed) === 1){
        $name = $parsed[0];

        $row = selectStorageByIdCodeParams($name);

        if($row){
            if($row['device_name'] === NULL || $row['public_key'] === NULL || $row['address'] === NULL){
                $_SESSION['error_message'] = "Talpykla neturi elektroninio užrakto! Pereikite prie inventoriaus pasiskolinimo!";
                header("Location: loan_inventory.php");
                exit();
            }

            $base64_encrypted_message = encryptMessage($row['public_key'], "unlock");

            $data_array = [
                'type' => "auth_confirmation",
                'message' => $base64_encrypted_message
            ];

            $output = send_data($data_array, $row['address']);

            if($output === "Success"){
                $_SESSION['success_message'] = "Talpykla sėkmingai atidaryta!";

                echo "<script>
                    window.addEventListener('load', (event) => {
                        if(document.getElementById('loan-tab')){
                            document.getElementById('loan-tab').click();
                        } else if (document.getElementById('return-tab')){
                            document.getElementById('return-tab').click();
                        }
                    });
                    </script>";

                return;
            } elseif($output === "Failed"){
                $_SESSION['error_message'] = "Talpyklos atidaryti nepavyko! Bandykite dar kartą!";
                header("Location: loan_inventory.php");
                exit();
            } else{
                $_SESSION['error_message'] = "Nepavyko užmegzti ryšio su elektroniniu užraktu!";
                header("Location: loan_inventory.php");
                exit();
            }
        } else{
            $_SESSION['error_message'] = "Identifikacinis kodas neteisingas!";
            header("Location: loan_inventory.php");
            exit();
        }
    } else{
        $_SESSION['error_message'] = "Identifikacinis kodas neteisingas!";
        header("Location: loan_inventory.php");
        exit(); 
    }
}
        #endregion

        #region Add Loan
function checkIfInventoryLoaned($inventory_id){
    global $conn;
    
    $stmt = mysqli_prepare($conn, "SELECT *
                                    FROM inventory_loans
                                    WHERE fk_inventory_id = ? AND `status` = 'Borrowed'");
    mysqli_stmt_bind_param($stmt, "i", $inventory_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    if($row){
        return true;
    } else{
        return false;
    }
}

function loanInventory($loan_id_code, $user_id){
    global $conn;

    $parsed = explode("__", $loan_id_code);

    if(count($parsed) === 3){
        $name = $parsed[0];
        $serial_number = $parsed[1];
        $inventory_number = $parsed[2];

        $date = date("Y-m-d");
        $return_until_date = date("Y-m-d", strtotime("+1 month"));

        $row = selectInventoryByIdCodeParams($name, $serial_number, $inventory_number);
        
        if($row){
            if(checkIfInventoryLoaned($row['id'])){
                $_SESSION['error_message'] = "Inventorius jau paskolintas!";
                return;
            } else{
                $stmt = mysqli_prepare($conn, "INSERT INTO inventory_loans(fk_user_id, fk_inventory_id, 
                                                        loan_date, return_until_date, `status`)
                                                VALUES(?, ?, ?, ?, 'Borrowed')");
                mysqli_stmt_bind_param($stmt, "iiss", $user_id, $row['id'], $date, $return_until_date);
                mysqli_stmt_execute($stmt);
                $affected_rows = mysqli_stmt_affected_rows($stmt);

                if($affected_rows > 0){
                    $_SESSION['success_message'] = "Inventoriaus panauda užregistruota!";
                    header("Location: loans.php");
                    exit();
                } else{
                    $_SESSION['error_message'] = "Inventoriaus panaudos užregistruoti nepavyko! Bandykite dar kartą!";
                    header("Location: loan_inventory.php");
                    exit();
                }
            }
        } else{
            $_SESSION['error_message'] = "Identifikacinis kodas neteisingas!";
            header("Location: loan_inventory.php");
            exit();
        }
    } else{
        $_SESSION['error_message'] = "Identifikacinis kodas neteisingas!";
        header("Location: loan_inventory.php");
        exit();
    }
}
        #endregion

        #region Return Loan
function returnInventory($return_id_code, $user_id){
    global $conn;

    $parsed = explode("__", $return_id_code);

    if(count($parsed) === 3){
        $name = $parsed[0];
        $serial_number = $parsed[1];
        $inventory_number = $parsed[2];

        $date = date("Y-m-d");

        $row = selectInventoryByIdCodeParams($name, $serial_number, $inventory_number);

        if($row){
            if(!checkIfInventoryLoaned($row['id'])){
                $_SESSION['error_message'] = "Inventorius dar nepasiskolintas!";
                return;
            } else{
                $stmt = mysqli_prepare($conn, "UPDATE inventory_loans
                                                SET `status` = 'Returned', return_date = ?
                                                WHERE fk_user_id = ? AND fk_inventory_id = ? AND `status` = 'Borrowed'");
                mysqli_stmt_bind_param($stmt, "sii", $date, $user_id, $row['id']);
                mysqli_stmt_execute($stmt);
                $affected_rows = mysqli_stmt_affected_rows($stmt);

                if($affected_rows > 0){
                    $_SESSION['success_message'] = "Inventoriaus grąžinimas užregistruotas!";
                    header("Location: loans.php");
                    exit();
                } else{
                    $_SESSION['error_message'] = "Inventoriaus grąžinimo užregistruoti nepavyko! Bandykite dar kartą!";
                    header("Location: return_inventory.php");
                    exit();
                }
            }
        } else{
            $_SESSION['error_message'] = "Identifikacinis kodas neteisingas!";
            header("Location: return_inventory.php");
            exit();
        }
    } else{
        $_SESSION['error_message'] = "Identifikacinis kodas neteisingas!";
        header("Location: return_inventory.php");
        exit();
    }
}
        #endregion
    #endregion
#endregion

#region Users
    #region Display Users
function display_users(){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT * 
                                    FROM users");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result;
}
    #endregion

    #region Change Role
function getUserById($user_id){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT *
                                    FROM users
                                    WHERE id =?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return $row;
}

function changeRole($user_id, $role){
    global $conn;

    $stmt = mysqli_prepare($conn, "UPDATE users
                                    SET `role` = ?
                                    WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $role, $user_id);
    mysqli_stmt_execute($stmt);
    $affected_rows = mysqli_stmt_affected_rows($stmt);

    if($affected_rows > 0){
        $_SESSION['success_message'] = "Naudotojo rolė atnaujinta sėkmingai!";
        header("Location: users.php");
        exit();
    } else{
        $row = getUserById($user_id);

        if($row['role'] === $role){
            $_SESSION['error_message'] = "Pasirinkta rolė atitinka jau esamą rolę!";
        }

        $_SESSION['error_message'] = "Rolės atnaujinti nepavyko! Bandykite dar kartą!";
        header("Location: users.php");
        exit();
    }
}
    #endregion

    #region Public Private Keys
function adminPrivatePublicKeys($user_id){
    global $server_base64_public_key;
    global $conn;

    $keypair = generateRandomKeyPair();

    $public_key = $keypair['public_key'];
    $private_key = $keypair['private_key'];
    
    $base64_public_key = base64_encode($public_key);
    $base64_private_key = base64_encode($private_key);

    $encrypted_base64_private_key = encryptMessage($server_base64_public_key, $base64_private_key);

    $stmt = mysqli_prepare($conn, "UPDATE users
                                    SET public_key = ?, private_key = ?
                                    WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "ssi", $base64_public_key, $encrypted_base64_private_key, $user_id);
    mysqli_stmt_execute($stmt);
    $affected_rows = mysqli_stmt_affected_rows($stmt);

    if($affected_rows > 0){
        return [
            'public_key' => $base64_public_key,
            'private_key' => $base64_private_key
        ];
        header("Location: users.php");
        exit();
    } else{
        $_SESSION['error_message'] = "Nepavyko sugeneruoti privataus/viešo raktų poros! Bandykite dar kartą!";
        header("Location: users.php");
        exit();
    }
}
    #endregion
#endregion

#region Admin Loan Requests
    #region Display Requests
function display_loan_requests_submitted(){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT loan_applications.*, users.name AS student_name, users.academic_group AS student_group, inventory.name AS inventory_name
                                    FROM loan_applications
                                    INNER JOIN users ON loan_applications.fk_user_id = users.id
                                    INNER JOIN inventory ON loan_applications.fk_inventory_id = inventory.id
                                    WHERE status = 'submitted'");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result;
}

function display_loan_requests_corrected(){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT loan_applications.*, users.name AS student_name, users.academic_group AS student_group, inventory.name AS inventory_name
                                    FROM loan_applications
                                    INNER JOIN users ON loan_applications.fk_user_id = users.id
                                    INNER JOIN inventory ON loan_applications.fk_inventory_id = inventory.id
                                    WHERE status = 'corrected'");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result;
}

function display_loan_requests_needs_correction(){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT loan_applications.*, users.name AS student_name, users.academic_group AS student_group, inventory.name AS inventory_name
                                    FROM loan_applications
                                    INNER JOIN users ON loan_applications.fk_user_id = users.id
                                    INNER JOIN inventory ON loan_applications.fk_inventory_id = inventory.id
                                    WHERE status = 'needs_correction'");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result;
}

function display_loan_requests_approved(){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT loan_applications.*, users.name AS student_name, users.academic_group AS student_group, inventory.name AS inventory_name,
                                    CASE 
                                        WHEN MAX(CASE
                                            WHEN inventory_loans.status = 'Borrowed'
                                            THEN 1
                                            ELSE 0 END) = 1
                                        THEN 'Borrowed'
                                        ELSE 'Available' 
                                    END AS inventory_status
                                    FROM loan_applications
                                    INNER JOIN users ON loan_applications.fk_user_id = users.id
                                    INNER JOIN inventory ON loan_applications.fk_inventory_id = inventory.id
                                    LEFT JOIN inventory_loans ON loan_applications.fk_inventory_id = inventory_loans.fk_inventory_id
                                    WHERE loan_applications.status = 'approved'
                                    GROUP BY loan_applications.id");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result;
}

function display_loan_requests_rejected(){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT loan_applications.*, users.name AS student_name, users.academic_group AS student_group, inventory.name AS inventory_name
                                    FROM loan_applications
                                    INNER JOIN users ON loan_applications.fk_user_id = users.id
                                    INNER JOIN inventory ON loan_applications.fk_inventory_id = inventory.id
                                    WHERE status = 'rejected'");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result;
}

function display_loan_requests_finished(){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT loan_applications.*, users.name AS student_name, users.academic_group AS student_group, inventory.name AS inventory_name
                                    FROM loan_applications
                                    INNER JOIN users ON loan_applications.fk_user_id = users.id
                                    INNER JOIN inventory ON loan_applications.fk_inventory_id = inventory.id
                                    WHERE status ='done'");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result;
}
    #endregion

    #region Request Actions
function getInventoryDataForModal($inventory_id){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT inventory.*, inventory_locations.name AS location_name
                                    FROM inventory
                                    INNER JOIN inventory_locations ON inventory.fk_inventory_location_id = inventory_locations.id
                                    WHERE inventory.id = ?");
    mysqli_stmt_bind_param($stmt, "i", $inventory_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return $row;
}

function calculateFee($inventory_id){
    global $conn;
    $fee = 0;

    $stmt = mysqli_prepare($conn, "SELECT *
                                    FROM inventory_loans
                                    WHERE fk_inventory_id = ? AND `status` = 'Borrowed'");
    mysqli_stmt_bind_param($stmt, "i", $inventory_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if($row = mysqli_fetch_assoc($result)){
        $return_date = new DateTime($row['return_until_date']);
        $current_date = new DateTime();
        $return_date = date_time_set($return_date, 0, 0, 0);
        $current_date = date_time_set($current_date, 0, 0, 0);
        
        $interval = date_diff($return_date, $current_date);
        $days = date_interval_format($interval, '%r%a');

        if($days > 0){
            $fee = round($days * 0.1, 2);
        }
    }

    return $fee;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_inventory') {
    if (!isset($_GET['inventory_id']) || !is_numeric($_GET['inventory_id'])) {
        http_response_code(400);
        exit();
    }

    $data = getInventoryDataForModal($_GET['inventory_id']);

    $data['fee'] = calculateFee($_GET['inventory_id']);

    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function approveRequest($request_id){
    global $conn;

    mysqli_begin_transaction($conn);

    try{

    $stmt = mysqli_prepare($conn, "UPDATE loan_applications
                                    SET `status` = 'approved'
                                    WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    mysqli_stmt_execute($stmt);
    $affected_rows = mysqli_stmt_affected_rows($stmt);

    if($affected_rows > 0){
        $row = getLoanRequestByID($request_id);
        $mail_sent = sendFeedbackMail($row['user_email'], $row['inventory_name']);

        if($mail_sent){
            $_SESSION['success_message'] = "Prašymas patvirtintas sėkmingai!";
            mysqli_commit($conn);
            
            header("Location: loan_requests.php");
            exit();
        }
    }
    
    $_SESSION['error_message'] = "Prašymo patvirtinti nepavyko! Bandykite dar kartą!";
    mysqli_rollback($conn);

    header("Location: loan_requests.php");
    exit();
    
    } catch(Exception $e){
        $_SESSION['error_message'] = "Prašymo patvirtinti nepavyko! Bandykite dar kartą!";
        mysqli_rollback($conn);

        header("Location: loan_requests.php");
        exit();
    }
}

function rejectRequest($request_id){
    global $conn;

    mysqli_begin_transaction($conn);

    try{
        $stmt = mysqli_prepare($conn, "UPDATE loan_applications
                                        SET `status` = 'rejected'
                                        WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $request_id);
        mysqli_stmt_execute($stmt);
        $affected_rows = mysqli_stmt_affected_rows($stmt);

        if($affected_rows > 0){
            $row = getLoanRequestByID($request_id);
            $mail_sent = sendFeedbackMail($row['user_email'], $row['inventory_name']);

            if($mail_sent){
                $_SESSION['success_message'] = "Prašymas atmestas sėkmingai!";
                mysqli_commit($conn);
                
                header("Location: loan_requests.php");
                exit();
            }
        }
        
        $_SESSION['error_message'] = "Prašymo atmesti nepavyko! Bandykite dar kartą!";
        mysqli_rollback($conn);

        header("Location: loan_requests.php");
        exit();
    } catch(Exception $e){
        $_SESSION['error_message'] = "Prašymo atmesti nepavyko! Bandykite dar kartą!";
        mysqli_rollback($conn);

        header("Location: loan_requests.php");
        exit();
    }
}

function getLoanRequestByID($request_id){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT users.email AS user_email, inventory.name AS inventory_name
                                    FROM loan_applications
                                    INNER JOIN users ON loan_applications.fk_user_id = users.id
                                    INNER JOIN inventory ON loan_applications.fk_inventory_id = inventory.id
                                    WHERE loan_applications.id = ?");
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return $row;
}

function addFeedback($request_id, $feedback){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT feedback FROM loan_applications
                                    WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $original_data = mysqli_fetch_assoc($result);

    mysqli_begin_transaction($conn);

    try{
        $stmt = mysqli_prepare($conn, "UPDATE loan_applications
                                    SET feedback = ?, `status` = 'needs_correction'
                                    WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $feedback, $request_id);
        mysqli_stmt_execute($stmt);
        $affected_rows = mysqli_stmt_affected_rows($stmt);

        if($affected_rows > 0){
            if($original_data['feedback'] === $feedback){
                $_SESSION['error_message'] = "Įrašyti duomenys atitinka jau esamus duomenis!";
                header("Location: loan_requests.php");
                exit();
            }

            $row = getLoanRequestByID($request_id);
            $mail_sent = sendFeedbackMail($row['user_email'], $row['inventory_name']);

            if($mail_sent){
                $_SESSION['success_message'] = "Atsiliepimas išsiųstas sėkmingai!";
                mysqli_commit($conn);
                
                header("Location: loan_requests.php");
                exit();
            }
        }

        $_SESSION['error_message'] = "Atsiliepimo išsiųsti nepavyko! Galbūt nepateikėte jokių naujų duomenų! Bandykite dar kartą!";
        mysqli_rollback($conn);

        header("Location: loan_requests.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Atsiliepimo išsiųsti nepavyko! Bandykite dar kartą!";
        mysqli_rollback($conn);

        header("Location: loan_requests.php");
        exit();
    }
}

function registerLoan($user_id, $inventory_id, $start_date, $end_date){
    global $conn;

    $stmt = mysqli_prepare($conn, "INSERT INTO inventory_loans(fk_user_id, fk_inventory_id, loan_date, return_until_date, `status`)
                                    VALUES(?, ?, ?, ?, 'Borrowed')");
    mysqli_stmt_bind_param($stmt, "iiss", $user_id, $inventory_id, $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $affected_rows = mysqli_stmt_affected_rows($stmt);

    if($affected_rows > 0){
        $_SESSION['success_message'] = "Inventoriaus atsiėmimas užfiksuotas!";
    } else{
        $_SESSION['error_message'] = "Inventoriaus atsiėmimo užfiksuoti nepavyko! Bandykite dar kartą!";
    }

    header("Location: loan_requests.php");
    exit();
}

function registerReturn($id, $inventory_id){
    global $conn;

    $date = date("Y-m-d");

    mysqli_begin_transaction($conn);

    $stmt = mysqli_prepare($conn, "UPDATE inventory_loans
                                    SET return_date = ?, `status` = 'Returned'
                                    WHERE fk_inventory_id = ? AND `status` = 'Borrowed'");
    mysqli_stmt_bind_param($stmt, "si", $date, $inventory_id);
    mysqli_stmt_execute($stmt);
    $affected_rows = mysqli_stmt_affected_rows($stmt);

    if($affected_rows > 0){
        $stmt = mysqli_prepare($conn, "UPDATE loan_applications
                                        SET `status` = 'done'
                                        WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $affected_rows = mysqli_stmt_affected_rows($stmt);

        if($affected_rows > 0){
            mysqli_commit($conn);
            $_SESSION['success_message'] = "Inventoriaus grąžinimas užfiksuotas!";
        } else{
            mysqli_rollback($conn);
            $_SESSION['error_message'] = "Inventoriaus grąžinimo užfiksuoti nepavyko! Bandykite dar kartą!";
        }
    } else{
        $_SESSION['error_message'] = "Inventoriaus grąžinimo užfiksuoti nepavyko! Bandykite dar kartą!";
    }

    header("Location: loan_requests.php");
    exit();
}
    #endregion
#endregion

#region Student Loan Requests
    #region Display Requests
function display_student_loan_requests(){
    global $conn;
    $user_id = $_SESSION['user_id'];

    $stmt = mysqli_prepare($conn, "SELECT loan_applications.*, users.name AS student_name, users.academic_group AS student_group, inventory.name AS inventory_name
                                    FROM loan_applications
                                    INNER JOIN users ON loan_applications.fk_user_id = users.id
                                    INNER JOIN inventory ON loan_applications.fk_inventory_id = inventory.id
                                    WHERE fk_user_id = ? ");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result;
}

function inventoryCount(){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM inventory");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result;
}
    #endregion

    #region Create Request
function createApplication($inventory_id, $start_date, $end_date, $additional_comments){
    global $conn;

    if($additional_comments === ""){
        $additional_comments = NULL;
    }

    if($start_date !== "" && $end_date !== ""){
        $stmt = mysqli_prepare($conn, "INSERT INTO loan_applications(fk_user_id, fk_inventory_id, `start_date`, end_date,
                                                                additional_comments, `status`)
                                        VALUES(?, ?, ?, ?, ?, 'submitted')");
        mysqli_stmt_bind_param($stmt, "iisss", $_SESSION['user_id'], $inventory_id, $start_date, $end_date, $additional_comments);
        mysqli_stmt_execute($stmt);
        $affected_rows = mysqli_stmt_affected_rows($stmt);

        if($affected_rows > 0){
            $_SESSION['success_message'] = "Prašymas užregistruotas sėkmingai!";
            header("Location: student_loan_requests.php");
            exit();
        } else{
            $_SESSION['error_message'] = "Inventoriaus pridėti nepavyko! Bandykite dar kartą!";
            return;
        }
    } else{
        $_SESSION['error_message'] = "Pasirinkite datą!";
        return;
    }
}
    #endregion

    #region Update Request
function getLoanApplicationById($application_id){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT *
                                    FROM loan_applications
                                    WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $application_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return $row;
}

function updateRequest($application_id, $start_date, $end_date, $inventory_id, $additional_comments){
    global $conn;

    $row = getLoanApplicationById($application_id);

    mysqli_begin_transaction($conn);

    $stmt = mysqli_prepare($conn, "UPDATE loan_applications
                                    SET fk_inventory_id = ?, `start_date` = ?, end_date = ?, 
                                        additional_comments = ?, `status` = 'corrected'
                                    WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "isssi", $inventory_id, $start_date, $end_date, $additional_comments, $application_id);
    mysqli_stmt_execute($stmt);
    $affected_rows = mysqli_stmt_affected_rows($stmt);

    if($affected_rows > 0){
        if($row['start_date'] === $start_date && $row['end_date'] === $end_date && $row['fk_inventory_id'] == $inventory_id && 
            $row['additional_comments'] === $additional_comments){
            $_SESSION['error_message'] = "Įrašyti duomenys atitinka jau esamus duomenis!";
            mysqli_rollback($conn);
            return;
        }

        mysqli_commit($conn);
        $_SESSION['success_message'] = "Inventorius atnaujintas sėkmingai!";
        header("Location: student_loan_requests.php");
        exit();
    } else{
        $_SESSION['error_message'] = "Inventoriaus atnaujinti nepavyko! Bandykite dar kartą!";
        return;
    }
}
    #endregion

    #region Delete Request
function cancelRequest($application_id){
    global $conn;

    $stmt = mysqli_prepare($conn, "DELETE FROM loan_applications
                                    WHERE id =?");
    mysqli_stmt_bind_param($stmt, "i", $application_id);
    mysqli_stmt_execute($stmt);
    $affected_rows = mysqli_stmt_affected_rows($stmt);

    if($affected_rows > 0){
        $_SESSION['success_message'] = "Prašymas atšauktas sėkmingai!";
        header("Location: student_loan_requests.php");
        exit();
    } else{
        $_SESSION['error_message'] = "Prašymo atšaukti nepavyko! Bandykite dar kartą!";
        return;
    }
}
    #endregion
#endregion

#region Analysis

    #region Yearly loans by month
function loan_years(){
    global $conn;
    $year = array_fill(0, 2, 0);

    $current_date_parsed = explode("-", date("Y-m-d"));
    $current_year = (int)$current_date_parsed[0];
    $year[0] = $current_year;
    $year[1] = $current_year;

    $stmt = mysqli_prepare($conn, "SELECT * 
                                    FROM inventory_loans");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while($row = mysqli_fetch_assoc($result)){
        $start_date = $row['loan_date'];
        $end_date = $row['return_until_date'];
        $return_date = $row['return_date'];

        $start_date_parsed = explode("-", $start_date);
        $end_date_parsed = explode("-", $end_date);

        $start_year = (int)$start_date_parsed[0];
        $end_year = (int)$end_date_parsed[0];

        if($row['status'] === 'Borrowed'){
            $end_year = $current_year;
        } elseif($row['status'] === 'Returned'){
            $return_date_parsed = explode("-", $return_date);
            $return_year = (int)$return_date_parsed[0];

            $end_year = $return_year;
        }

        if($start_year < $year[0]){
            $year[0] = $start_year;
        } elseif($end_year > $year[1]){
            $year[1] = $end_year;
        }
    }

    return $year;
}

function calculate_year_loans_by_month($year){
    global $conn;
    $month = array_fill(0, 12, 0);

    $stmt = mysqli_prepare($conn, "SELECT * 
                                    FROM inventory_loans");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while($row = mysqli_fetch_assoc($result)){
        $start_date = $row['loan_date'];
        $end_date = $row['return_until_date'];
        $return_date = $row['return_date'];

        $start_date_parsed = explode("-", $start_date);
        $end_date_parsed = explode("-", $end_date);
        $current_date_parsed = explode("-", date("Y-m-d"));

        $start_year = (int)$start_date_parsed[0];
        $end_year = (int)$end_date_parsed[0];
        $start_month = (int)$start_date_parsed[1];
        $end_month = (int)$end_date_parsed[1];
        
        $current_year = (int)$current_date_parsed[0];
        $current_month = (int)$current_date_parsed[1];

        if($row['status'] === 'Borrowed'){
            $end_year = $current_year;
            $end_month = $current_month;
        } elseif($row['status'] === 'Returned'){
            $return_date_parsed = explode("-", $return_date);
            $return_year = (int)$return_date_parsed[0];
            $return_month = (int)$return_date_parsed[1];

            $end_year = $return_year;
            $end_month = $return_month;
        }

        for($i = 1; $i < 13; $i++){
            if($start_year === $year && $end_year === $year){
                if($start_month === $i || $end_month === $i){
                    $month[$i - 1]++;
                } elseif($i > $start_month && $i < $end_month){
                    $month[$i - 1]++;
                }
            } elseif($start_year === $year && $end_year > $year){
                if($start_month === $i || 12 === $i){
                    $month[$i - 1]++;
                } elseif($i > $start_month && $i < 12){
                    $month[$i - 1]++;
                }
            } elseif($start_year < $year && $end_year === $year){
                if(1 === $i || $end_month === $i){
                    $month[$i - 1]++;
                } elseif($i > 1 && $i < $end_month){
                    $month[$i - 1]++;
                }
            } elseif($start_year < $year && $end_year > $year){
                if(1 === $i || 12 === $i){
                    $month[$i - 1]++;
                } elseif($i > 1 && $i < 12){
                    $month[$i - 1]++;
                }
            }
        }
    }

    return $month;
}
    #endregion

    #region Ana
function calculate_year_returned_and_not_returned_in_time_loans($year){
    global $conn;
    $data = array_fill(0, 4, 0);

    $stmt = mysqli_prepare($conn, "SELECT *
                                    FROM inventory_loans");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
        $start_date = $row['loan_date'];
        $end_date = $row['return_until_date'];
        $return_date = $row['return_date'];

        $start_date_parsed = explode("-", $start_date);
        $end_date_parsed = explode("-", $end_date);
        $return_date_parsed = explode("-", $return_date);

        $start_year = (int)$start_date_parsed[0];
        $end_year = (int)$end_date_parsed[0];

        $end_month = (int)$end_date_parsed[1];

        $end_day = (int)$end_date_parsed[2];

        if($return_date){
            $return_year = (int)$return_date_parsed[0];
            $return_month = (int)$return_date_parsed[1];
            $return_day = (int)$return_date_parsed[2];

            if($return_year === $year && $return_year === $end_year && ($return_month < $end_month || ($return_month === $end_month && $return_day <= $end_day))){
                $data[0]++;
                $data[1]++;
            } elseif($start_year === $year && $return_year === $end_year && ($return_month < $end_month || ($return_month === $end_month && $return_day <= $end_day))){
                $data[0]++;
                $data[1]++;
            } elseif($start_year === $year && ($return_year > $end_year || ($return_year === $end_year && $return_month > $end_month) || ($return_year === $end_year && $return_month === $end_month && $return_day > $end_day))){
                $data[0]++;
                $data[2]++;
            }
        } elseif (!$return_date && $start_year === $year) {
            $data[0]++;
            $data[3]++;
        }
    }

    return $data;
}
    #endregion
#endregion

#region Mail
function sendEmailVerificationMail($recipient, $verification_token){
    $headers = "From: KTUIVS <reflexxion.usage@gmail.com>";
    $to = $recipient;
    $subject = "El. pašto adreso patvirtinimas KTUIVS";

    $message = "Sveiki,\n\n";
    $message .= "Sveikiname prisijungus prie KTUIVS sistemos.\n\n";
    $message .= "Paspauskite ant nuorodos, jog patvirtintumėte savo elektroninio pašto adresą: https://ktuivs.reflexxion.lt/email_verification.php?token=$verification_token\n";
    $message .= "Jeigu nesiregistravote prie sistemos KTUIVS, ignoruokite šį laišką.\n\n";
    $message .= "Nebandykite atsakyti į šią žinutę. Tai yra automatinis pranešimas.\n\n";
    $message .= "Pagarbiai,\n";
    $message .= "KTUIVS";

    if(mail($to, $subject, $message, $headers)){
        return true;
    } else{
        return false;
    }
}

function sendFeedbackMail($recipient, $inventory){
    $headers = "From: KTUIVS reflexxion.usage@gmail.com";
    $to = "tankiuks9@gmail.com"; // PAKEISTI Į $recipient!!!
    $subject = "Pasikeitė jūsų paskolos prašymo statusas sistemoje KTUIVS";

    $message = "Sveiki,\n\n";
    $message .= "Pasikeitė jūsų inventoriaus (" . $inventory . ") paskolos prašymo statusas. Inventoriaus paskolos prašymo statusą galite patikrinti prisijungę prie savo KTUIVS paskyros.\n\n";
    $message .= "Nebandykite atsakyti į šią žinutę. Tai yra automatinis pranešimas.\n\n";
    $message .= "Pagarbiai,\n";
    $message .= "KTUIVS";

    if(mail($to, $subject, $message, $headers)){
        return true;
    } else{
        return false;
    }
}
#endregion

#region Crypto
function getPrivateKey(){
    global $server_base64_private_key;
    return $server_base64_private_key;
}

function getPublicKey(){
    global $server_base64_public_key;
    return $server_base64_public_key;
}

function getStorageByDeviceName($device_name){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT *
                                    FROM inventory_locations
                                    WHERE device_name = ?");
    mysqli_stmt_bind_param($stmt, "s", $device_name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return $row;
}

function generateRandomKeyPair() {
    $keypair = sodium_crypto_box_keypair();

    $public_key = sodium_crypto_box_publickey($keypair);
    $private_key = sodium_crypto_box_secretkey($keypair);

    return [
        'public_key' => $public_key,
        'private_key' => $private_key
    ];
}

function generateRandomString() {
    $length = 10;

    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }

    return $randomString;
}

function encryptMessage($recipient_base64_public_key, $message) {
    $recipient_public_key = base64_decode($recipient_base64_public_key);

    $ciphertext = sodium_crypto_box_seal($message, $recipient_public_key);

    $encrypted_message = base64_encode($ciphertext);

    return $encrypted_message;
}

function decryptMessage($base64_private_key, $base64_public_key, $base64_encrypted_message) {
    try{
        $private_key = base64_decode($base64_private_key);
        $public_key = base64_decode($base64_public_key);
        $encrypted_message = base64_decode($base64_encrypted_message);

        $keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey($private_key, $public_key);

        $decrypted = sodium_crypto_box_seal_open($encrypted_message, $keypair);

        if ($decrypted === false) {
            echo "Decryption failed! Possible reasons:\n";
            echo "- Message was tampered with\n";
            echo "- Wrong keys used\n";
            echo "- Corrupted message\n";
        }

        return $decrypted;
    } catch (Exception $e) {
        return false;
    }
}

function send_data($data_array, $addr) {
    $data = http_build_query($data_array);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $addr . "/post");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $output = curl_exec($ch);
    curl_close($ch);

    return $output;
}

function removeRandomStringRecord($device_name){
    global $conn;

    $stmt = mysqli_prepare($conn, "DELETE FROM device_authentication_messages
                                    WHERE device_name = ?");
    mysqli_stmt_bind_param($stmt, "s", $device_name);
    mysqli_stmt_execute($stmt);
}

function storeRandomStringInDB($device_name, $randomString) {
    global $conn;

    $stmt = mysqli_prepare($conn, "INSERT INTO device_authentication_messages (device_name, `message`)
                                    VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ss", $device_name, $randomString);
    if (mysqli_stmt_execute($stmt)) {
       return true; 
    } else {
        return false;
    }
}

function getRandomStringFromDB($device_name) {
    global $conn;
    $message = "";

    $stmt = mysqli_prepare($conn, "SELECT * FROM device_authentication_messages
                                    WHERE device_name = ?
                                    ORDER BY id DESC");
    mysqli_stmt_bind_param($stmt, "s", $device_name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $message = $row['message'];
    }

    removeRandomStringRecord($device_name);

    return $message;
}

function adminCardData($data){
    global $server_base64_public_key;

    $recipient_public_key = base64_decode($server_base64_public_key);

    $ciphertext = sodium_crypto_box_seal($data, $recipient_public_key);

    $encrypted_message = base64_encode($ciphertext);

    return $encrypted_message;
}

if((isset($_POST['action']) && $_POST['action'] === 'generate_admin_card_data')) {
    $data = $_POST['user_id'] . "__" . $_POST['user_name'];
    $result['data'] = adminCardData($data);

    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}

function registerStorageUnlockAttempt($device_name, $user_id, $result){
    global $conn;

    $device_id = getStorageByDeviceName($device_name)['id']; 

    $stmt = mysqli_prepare($conn, "INSERT INTO storage_unlock_attempts (fk_user_id, fk_inventory_location_id, result)
                                    VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iis", $user_id, $device_id, $result);
    mysqli_stmt_execute($stmt);
}

function authenticateUser($user_id, $user_name, $device_name){
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT * FROM users
                                    WHERE id = ?
                                    AND `name` = ?");
    mysqli_stmt_bind_param($stmt, "is", $user_id, $user_name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        return true;
    } else {
        return false;
    }
}
#endregion
?>