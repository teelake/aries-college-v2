<?php
session_start();
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// SMTP settings (FILL THESE IN)
$smtpHost = 'mail.achtech.org.ng'; // e.g., mail.achtech.org.ng
$smtpUsername = 'no-reply@achtech.org.ng';
$smtpPassword = 'Temp_pass123';
$smtpPort = 465; // 465 for SSL, 587 for TLS
$smtpFrom = 'no-reply@achtech.org.ng';
$smtpFromName = 'Aries College';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection (inlined)
    $DB_HOST = 'localhost';
    $DB_USER = 'achtecho_user';
    $DB_PASS = '2fvW!GSO30,Y8{R&';
    $DB_NAME = 'achtecho_db';
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);
    function clean($data, $conn) {
        return htmlspecialchars($conn->real_escape_string(trim($data)));
    }
    $fullName = clean($_POST['fullName'] ?? '', $conn);
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ? clean($_POST['email'], $conn) : '';
    $phone = clean($_POST['phone'] ?? '', $conn);
    $dateOfBirth = clean($_POST['dateOfBirth'] ?? '', $conn);
    $gender = clean($_POST['gender'] ?? '', $conn);
    $address = clean($_POST['address'] ?? '', $conn);
    $state = clean($_POST['state'] ?? '', $conn);
    $lga = clean($_POST['lga'] ?? '', $conn);
    $lastSchool = clean($_POST['lastSchool'] ?? '', $conn);
    $qualification = clean($_POST['qualification'] ?? '', $conn);
    $yearCompleted = clean($_POST['yearCompleted'] ?? '', $conn);
    $course = clean($_POST['course'] ?? '', $conn);
    // File uploads (photo, certificate)
    $photoPath = '';
    $certificatePath = '';
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photoExt = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photoPath = $uploadDir . uniqid('photo_') . '.' . $photoExt;
        move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath);
    }
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
        $certExt = pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION);
        $certificatePath = $uploadDir . uniqid('cert_') . '.' . $certExt;
        move_uploaded_file($_FILES['certificate']['tmp_name'], $certificatePath);
    }
    if (!$fullName || !$email || !$phone || !$dateOfBirth || !$gender || !$address || !$state || !$lga || !$qualification || !$yearCompleted || !$course || !$photoPath || !$certificatePath) {
        $_SESSION['form_message'] = ['type' => 'error', 'text' => 'Please fill all required fields and upload required documents.'];
        header('Location: apply.php');
        exit;
    }
    // Check for duplicate email or phone
    $dupStmt = $conn->prepare("SELECT id FROM applications WHERE email = ? OR phone = ? LIMIT 1");
    $dupStmt->bind_param("ss", $email, $phone);
    $dupStmt->execute();
    $dupStmt->store_result();
    if ($dupStmt->num_rows > 0) {
        $_SESSION['form_message'] = ['type' => 'error', 'text' => 'An application with this email or phone number already exists. Please use a different email or phone.'];
        $dupStmt->close();
        $conn->close();
        header('Location: apply.php');
        exit;
    }
    $dupStmt->close();
    $stmt = $conn->prepare("INSERT INTO applications (full_name, email, phone, date_of_birth, gender, address, state, lga, last_school, qualification, year_completed, program_applied, photo_path, certificate_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssssss", $fullName, $email, $phone, $dateOfBirth, $gender, $address, $state, $lga, $lastSchool, $qualification, $yearCompleted, $course, $photoPath, $certificatePath);
    if ($stmt->execute()) {
        // Send email to applicant with all form details using PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $smtpPort;
            $mail->setFrom($smtpFrom, $smtpFromName);
            $mail->addAddress($email, $fullName);
            $mail->Subject = "Application Received - Aries College";
            $msg = "Dear $fullName,\n\nYour application has been received. Here is a summary of your application:\n\n";
            $msg .= "Full Name: $fullName\n";
            $msg .= "Email: $email\n";
            $msg .= "Phone: $phone\n";
            $msg .= "Date of Birth: $dateOfBirth\n";
            $msg .= "Gender: $gender\n";
            $msg .= "Address: $address\n";
            $msg .= "State: $state\n";
            $msg .= "LGA: $lga\n";
            $msg .= "Last School: $lastSchool\n";
            $msg .= "Qualification: $qualification\n";
            $msg .= "Year Completed: $yearCompleted\n";
            $msg .= "Program Applied: $course\n";
            $msg .= "\n---\n";
            $msg .= "This is a confirmation of your application.\n";
            $msg .= "Application Fee: ₦10,000 (Ten Thousand Naira)\n";
            $msg .= "Payment will be processed through Remita payment gateway.\n";
            $mail->Body = $msg;
            $mail->send();
            $_SESSION['form_message'] = ['type' => 'success', 'text' => 'Application received! We have sent you an acknowledgment email.'];
        } catch (Exception $e) {
            $_SESSION['form_message'] = ['type' => 'error', 'text' => 'Your application was saved, but we could not send an acknowledgment email. Mailer Error: ' . $mail->ErrorInfo];
        }
    } else {
        $_SESSION['form_message'] = ['type' => 'error', 'text' => 'Error: ' . $conn->error];
    }
    $stmt->close();
    $conn->close();
    header('Location: apply.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Now - Aries College of Health Management & Technology</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="img/logo.png">
    <style>
    .form-message.success {
        background: #d1fae5;
        color: #065f46;
        border-left: 6px solid #10b981;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        border-radius: 8px;
        font-weight: 500;
    }
    .form-message.error {
        background: #fee2e2;
        color: #991b1b;
        border-left: 6px solid #ef4444;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        border-radius: 8px;
        font-weight: 500;
    }
    </style>
</head>
<body>
    <!-- Header Placeholder -->
    <div id="main-header"></div>
    <section class="section bg-light">
        <div class="container">
            <h2 class="section-title">Application Form</h2>
            <p class="section-subtitle">Fill out the form below to apply for admission</p>
            <?php if (isset($_SESSION['form_message'])): ?>
                <div class="form-message <?php echo $_SESSION['form_message']['type']; ?>">
                    <?php echo htmlspecialchars($_SESSION['form_message']['text']); ?>
                </div>
                <?php unset($_SESSION['form_message']); ?>
            <?php endif; ?>
            <form id="applicationForm" action="" method="POST" enctype="multipart/form-data">
                <div class="progress-container">
                    <div class="progress-step active">
                        <div class="progress-number">1</div>
                        <span>Personal Info</span>
                    </div>
                    <div class="progress-step">
                        <div class="progress-number">2</div>
                        <span>Academic Info</span>
                    </div>
                    <div class="progress-step">
                        <div class="progress-number">3</div>
                        <span>Uploads & Payment</span>
                    </div>
                </div>
                <div class="form-step active" data-step="1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="fullName">Full Name *</label>
                            <input type="text" id="fullName" name="fullName" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="dateOfBirth">Date of Birth *</label>
                            <input type="date" id="dateOfBirth" name="dateOfBirth" required>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender *</label>
                            <select id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="address">Address *</label>
                            <input type="text" id="address" name="address" required>
                        </div>
                        <div class="form-group">
                            <label for="state">State *</label>
                            <select id="state" name="state" required>
                                <option value="">Select State</option>
                                <option value="Abia">Abia</option>
                                <option value="Adamawa">Adamawa</option>
                                <option value="Akwa Ibom">Akwa Ibom</option>
                                <option value="Anambra">Anambra</option>
                                <option value="Bauchi">Bauchi</option>
                                <option value="Bayelsa">Bayelsa</option>
                                <option value="Benue">Benue</option>
                                <option value="Borno">Borno</option>
                                <option value="Cross River">Cross River</option>
                                <option value="Delta">Delta</option>
                                <option value="Ebonyi">Ebonyi</option>
                                <option value="Edo">Edo</option>
                                <option value="Ekiti">Ekiti</option>
                                <option value="Enugu">Enugu</option>
                                <option value="Gombe">Gombe</option>
                                <option value="Imo">Imo</option>
                                <option value="Jigawa">Jigawa</option>
                                <option value="Kaduna">Kaduna</option>
                                <option value="Kano">Kano</option>
                                <option value="Katsina">Katsina</option>
                                <option value="Kebbi">Kebbi</option>
                                <option value="Kogi">Kogi</option>
                                <option value="Kwara">Kwara</option>
                                <option value="Lagos">Lagos</option>
                                <option value="Nasarawa">Nasarawa</option>
                                <option value="Niger">Niger</option>
                                <option value="Ogun">Ogun</option>
                                <option value="Ondo">Ondo</option>
                                <option value="Osun">Osun</option>
                                <option value="Oyo">Oyo</option>
                                <option value="Plateau">Plateau</option>
                                <option value="Rivers">Rivers</option>
                                <option value="Sokoto">Sokoto</option>
                                <option value="Taraba">Taraba</option>
                                <option value="Yobe">Yobe</option>
                                <option value="Zamfara">Zamfara</option>
                                <option value="FCT">FCT</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="lga">LGA *</label>
                            <select id="lga" name="lga" required>
                                <option value="">Select LGA</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-navigation">
                        <button type="button" class="btn btn-primary next-step">Next</button>
                    </div>
                </div>
                <div class="form-step" data-step="2">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="lastSchool">Last School Attended</label>
                            <input type="text" id="lastSchool" name="lastSchool">
                        </div>
                        <div class="form-group">
                            <label for="qualification">Qualification *</label>
                            <select id="qualification" name="qualification" required>
                                <option value="">Select Qualification</option>
                                <option value="WAEC">WAEC</option>
                                <option value="NECO">NECO</option>
                                <option value="NABTEB">NABTEB</option>
                              
                        
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="yearCompleted">Year Completed *</label>
                            <input type="date" id="yearCompleted" name="yearCompleted" required>
                        </div>
                        <div class="form-group">
                            <label for="course">Which program are you applying for? *</label>
                            <select id="course" name="course" required>
                                <option value="">Select Program</option>
                                <option value="Community Health">Community Health</option>
                                <option value="Public Health">Public Health</option>
                                <option value="Health Information Management">Health Information Management</option>
                                <option value="Social Work">Social Work</option>
                                <option value="Hospitality Management">Hospitality Management</option>
                                <option value="Medical Store Management Technology">Medical Store Management Technology</option>
                                <option value="Paramedics">Paramedics</option>
                                <option value="Hospital Administration and Healthcare Management">Hospital Administration and Healthcare Management</option>
                                <option value="Health Care Technician">Health Care Technician</option>
                                <option value="Environmental Health">Environmental Health</option>
                                <option value="Pharmacy Technician">Pharmacy Technician</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-navigation">
                        <button type="button" class="btn btn-outline prev-step">Previous</button>
                        <button type="button" class="btn btn-primary next-step">Next</button>
                    </div>
                </div>
                <div class="form-step" data-step="3">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="photo">Passport Photo (JPG/PNG) *</label>
                            <input type="file" id="photo" name="photo" accept="image/*" required>
                        </div>
                        <div class="form-group">
                            <label for="certificate">Certificate (PDF/JPG/PNG) *</label>
                            <input type="file" id="certificate" name="certificate" accept=".pdf,image/*" required>
                        </div>
                    </div>
                    <div class="form-navigation">
                        <button type="button" class="btn btn-outline prev-step">Previous</button>
                        <button type="submit" class="btn btn-primary btn-lg">Submit Application & Pay ₦10,000</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
    <!-- Footer Placeholder -->
    <div id="main-footer"></div>
    <!-- Dynamic Header/Footer Loading Script -->
    <script>
document.addEventListener("DOMContentLoaded", function() {
    // Load header
    fetch('header.html')
        .then(response => response.text())
        .then(data => {
            document.getElementById('main-header').innerHTML = data;
            // Set active nav link for current page
            const currentPage = window.location.pathname.split('/').pop() || 'index.html';
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('active');
                }
            });
            // Re-initialize navbar after header is loaded
            if (typeof initNavbar === 'function') {
                initNavbar();
            }
        })
        .catch(error => console.error('Error loading header:', error));
    // Load footer
    fetch('footer.html')
        .then(response => response.text())
        .then(data => {
            document.getElementById('main-footer').innerHTML = data;
        })
        .catch(error => console.error('Error loading footer:', error));
});
</script>
    <script src="js/main.js"></script>
    <script>
// Nigerian States and LGAs mapping
const stateLgas = {
    "Abia": ["Aba North", "Aba South", "Arochukwu", "Bende", "Ikwuano", "Isiala Ngwa North", "Isiala Ngwa South", "Isuikwuato", "Obi Ngwa", "Ohafia", "Osisioma", "Ugwunagbo", "Ukwa East", "Ukwa West", "Umuahia North", "Umuahia South", "Umu Nneochi"],
    "Adamawa": ["Demsa", "Fufore", "Ganye", "Gayuk", "Gombi", "Grie", "Hong", "Jada", "Lamurde", "Madagali", "Maiha", "Mayo Belwa", "Michika", "Mubi North", "Mubi South", "Numan", "Shelleng", "Song", "Toungo", "Yola North", "Yola South"],
    "Akwa Ibom": ["Abak", "Eastern Obolo", "Eket", "Esit Eket", "Essien Udim", "Etim Ekpo", "Etinan", "Ibeno", "Ibesikpo Asutan", "Ibiono Ibom", "Ika", "Ikono", "Ikot Abasi", "Ikot Ekpene", "Ini", "Itu", "Mbo", "Mkpat Enin", "Nsit Atai", "Nsit Ibom", "Nsit Ubium", "Obot Akara", "Okobo", "Onna", "Oron", "Oruk Anam", "Udung Uko", "Ukanafun", "Uruan", "Urue-Offong/Oruko", "Uyo"],
    "Anambra": ["Aguata", "Anambra East", "Anambra West", "Anaocha", "Awka North", "Awka South", "Ayamelum", "Dunukofia", "Ekwusigo", "Idemili North", "Idemili South", "Ihiala", "Njikoka", "Nnewi North", "Nnewi South", "Ogbaru", "Onitsha North", "Onitsha South", "Orumba North", "Orumba South", "Oyi"],
    "Bauchi": ["Alkaleri", "Bauchi", "Bogoro", "Damban", "Darazo", "Dass", "Gamawa", "Ganjuwa", "Giade", "Itas/Gadau", "Jama'are", "Katagum", "Kirfi", "Misau", "Ningi", "Shira", "Tafawa Balewa", "Toro", "Warji", "Zaki"],
    "Bayelsa": ["Brass", "Ekeremor", "Kolokuma/Opokuma", "Nembe", "Ogbia", "Sagbama", "Southern Ijaw", "Yenagoa"],
    "Benue": ["Ado", "Agatu", "Apa", "Buruku", "Gboko", "Guma", "Gwer East", "Gwer West", "Katsina-Ala", "Konshisha", "Kwande", "Logo", "Makurdi", "Obi", "Ogbadibo", "Ohimini", "Oju", "Okpokwu", "Otukpo", "Tarka", "Ukum", "Ushongo", "Vandeikya"],
    "Borno": ["Abadam", "Askira/Uba", "Bama", "Bayo", "Biu", "Chibok", "Damboa", "Dikwa", "Gubio", "Guzamala", "Gwoza", "Hawul", "Jere", "Kaga", "Kala/Balge", "Konduga", "Kukawa", "Kwaya Kusar", "Mafa", "Magumeri", "Maiduguri", "Marte", "Mobbar", "Monguno", "Ngala", "Nganzai", "Shani"],
    "Cross River": ["Abi", "Akamkpa", "Akpabuyo", "Bakassi", "Bekwarra", "Biase", "Boki", "Calabar Municipal", "Calabar South", "Etung", "Ikom", "Obanliku", "Obubra", "Obudu", "Odukpani", "Ogoja", "Yakurr", "Yala"],
    "Delta": ["Aniocha North", "Aniocha South", "Bomadi", "Burutu", "Ethiope East", "Ethiope West", "Ika North East", "Ika South", "Isoko North", "Isoko South", "Ndokwa East", "Ndokwa West", "Okpe", "Oshimili North", "Oshimili South", "Patani", "Sapele", "Udu", "Ughelli North", "Ughelli South", "Ukwuani", "Uvwie", "Warri North", "Warri South", "Warri South West"],
    "Ebonyi": ["Abakaliki", "Afikpo North", "Afikpo South", "Ebonyi", "Ezza North", "Ezza South", "Ikwo", "Ishielu", "Ivo", "Izzi", "Ohaozara", "Ohaukwu", "Onicha"],
    "Edo": ["Akoko-Edo", "Egor", "Esan Central", "Esan North-East", "Esan South-East", "Esan West", "Etsako Central", "Etsako East", "Etsako West", "Igueben", "Ikpoba-Okha", "Oredo", "Orhionmwon", "Ovia North-East", "Ovia South-West", "Owan East", "Owan West", "Uhunmwonde"],
    "Ekiti": ["Ado Ekiti", "Efon", "Ekiti East", "Ekiti South-West", "Ekiti West", "Emure", "Gbonyin", "Ido Osi", "Ijero", "Ikere", "Ikole", "Ilejemeje", "Irepodun/Ifelodun", "Ise/Orun", "Moba", "Oye"],
    "Enugu": ["Aninri", "Awgu", "Enugu East", "Enugu North", "Enugu South", "Ezeagu", "Igbo Etiti", "Igbo Eze North", "Igbo Eze South", "Isi Uzo", "Nkanu East", "Nkanu West", "Nsukka", "Oji River", "Udenu", "Udi", "Uzo Uwani"],
    "Gombe": ["Akko", "Balanga", "Billiri", "Dukku", "Funakaye", "Gombe", "Kaltungo", "Kwami", "Nafada", "Shongom", "Yamaltu/Deba"],
    "Imo": ["Aboh Mbaise", "Ahiazu Mbaise", "Ehime Mbano", "Ezinihitte", "Ideato North", "Ideato South", "Ihitte/Uboma", "Ikeduru", "Isiala Mbano", "Isu", "Mbaitoli", "Ngor Okpala", "Njaba", "Nkwerre", "Nwangele", "Obowo", "Oguta", "Ohaji/Egbema", "Okigwe", "Onuimo", "Orlu", "Orsu", "Oru East", "Oru West", "Owerri Municipal", "Owerri North", "Owerri West"],
    "Jigawa": ["Auyo", "Babura", "Biriniwa", "Birnin Kudu", "Buji", "Dutse", "Gagarawa", "Garki", "Gumel", "Guri", "Gwaram", "Gwiwa", "Hadejia", "Jahun", "Kafin Hausa", "Kaugama", "Kazaure", "Kiri Kasama", "Kiyawa", "Maigatari", "Malam Madori", "Miga", "Ringim", "Roni", "Sule Tankarkar", "Taura", "Yankwashi"],
    "Kaduna": ["Birnin Gwari", "Chikun", "Giwa", "Igabi", "Ikara", "Jaba", "Jema'a", "Kachia", "Kaduna North", "Kaduna South", "Kagarko", "Kajuru", "Kaura", "Kauru", "Kubau", "Kudan", "Lere", "Makarfi", "Sabon Gari", "Sanga", "Soba", "Zangon Kataf", "Zaria"],
    "Kano": ["Ajingi", "Albasu", "Bagwai", "Bebeji", "Bichi", "Bunkure", "Dala", "Dambatta", "Dawakin Kudu", "Dawakin Tofa", "Doguwa", "Fagge", "Gabasawa", "Garko", "Garun Mallam", "Gaya", "Gezawa", "Gwale", "Gwarzo", "Kabo", "Kano Municipal", "Karaye", "Kibiya", "Kiru", "Kumbotso", "Kunchi", "Kura", "Madobi", "Makoda", "Minjibir", "Nasarawa", "Rano", "Rimin Gado", "Rogo", "Shanono", "Sumaila", "Takai", "Tarauni", "Tofa", "Tsanyawa", "Tudun Wada", "Ungogo", "Warawa", "Wudil"],
    "Katsina": ["Bakori", "Batagarawa", "Batsari", "Baure", "Bindawa", "Charanchi", "Dandume", "Danja", "Dan Musa", "Daura", "Dutsi", "Dutsin Ma", "Faskari", "Funtua", "Ingawa", "Jibia", "Kafur", "Kaita", "Kankara", "Kankia", "Katsina", "Kurfi", "Kusada", "Mai'Adua", "Malumfashi", "Mani", "Mashi", "Matazu", "Musawa", "Rimi", "Sabuwa", "Safana", "Sandamu", "Zango"],
    "Kebbi": ["Aleiro", "Arewa Dandi", "Argungu", "Augie", "Bagudo", "Birnin Kebbi", "Bunza", "Dandi", "Fakai", "Gwandu", "Jega", "Kalgo", "Koko/Besse", "Maiyama", "Ngaski", "Sakaba", "Shanga", "Suru", "Wasagu/Danko", "Yauri", "Zuru"],
    "Kogi": ["Adavi", "Ajaokuta", "Ankpa", "Bassa", "Dekina", "Ibaji", "Idah", "Igalamela Odolu", "Ijumu", "Kabba/Bunu", "Kogi", "Lokoja", "Mopa-Muro", "Ofu", "Ogori/Magongo", "Okehi", "Okene", "Olamaboro", "Omala", "Yagba East", "Yagba West"],
    "Kwara": ["Asa", "Baruten", "Edu", "Ekiti", "Ifelodun", "Ilorin East", "Ilorin South", "Ilorin West", "Irepodun", "Isin", "Kaiama", "Moro", "Offa", "Oke Ero", "Oyun", "Pategi"],
    "Lagos": ["Agege", "Ajeromi-Ifelodun", "Alimosho", "Amuwo-Odofin", "Apapa", "Badagry", "Epe", "Eti Osa", "Ibeju-Lekki", "Ifako-Ijaiye", "Ikeja", "Ikorodu", "Kosofe", "Lagos Island", "Lagos Mainland", "Mushin", "Ojo", "Oshodi-Isolo", "Shomolu", "Surulere"],
    "Nasarawa": ["Akwanga", "Awe", "Doma", "Karu", "Keana", "Kokona", "Lafia", "Nasarawa", "Nasarawa Egon", "Obi", "Toto", "Wamba"],
    "Niger": ["Agaie", "Agwara", "Bida", "Borgu", "Bosso", "Chanchaga", "Edati", "Gbako", "Gurara", "Katcha", "Kontagora", "Lapai", "Lavun", "Magama", "Mariga", "Mashegu", "Mokwa", "Moya", "Paikoro", "Rafi", "Rijau", "Shiroro", "Suleja", "Tafa", "Wushishi"],
    "Ogun": ["Abeokuta North", "Abeokuta South", "Ado-Odo/Ota", "Egbado North", "Egbado South", "Ewekoro", "Ifo", "Ijebu East", "Ijebu North", "Ijebu North East", "Ijebu Ode", "Ikenne", "Imeko Afon", "Ipokia", "Obafemi Owode", "Odeda", "Odogbolu", "Ogun Waterside", "Remo North", "Shagamu"],
    "Ondo": ["Akoko North-East", "Akoko North-West", "Akoko South-West", "Akoko South-East", "Akure North", "Akure South", "Ese Odo", "Idanre", "Ifedore", "Ilaje", "Ile Oluji/Okeigbo", "Irele", "Odigbo", "Okitipupa", "Ondo East", "Ondo West", "Ose", "Owo"],
    "Osun": ["Aiyedade", "Aiyedire", "Atakumosa East", "Atakumosa West", "Boluwaduro", "Boripe", "Ede North", "Ede South", "Egbedore", "Ejigbo", "Ife Central", "Ife East", "Ife North", "Ife South", "Ifedayo", "Ifelodun", "Ila", "Ilesa East", "Ilesa West", "Irepodun", "Irewole", "Isokan", "Iwo", "Obokun", "Odo Otin", "Ola Oluwa", "Olorunda", "Oriade", "Orolu", "Osogbo"],
    "Oyo": ["Afijio", "Akinyele", "Atiba", "Atisbo", "Egbeda", "Ibadan North", "Ibadan North-East", "Ibadan North-West", "Ibadan South-East", "Ibadan South-West", "Ibarapa Central", "Ibarapa East", "Ibarapa North", "Ido", "Irepo", "Iseyin", "Itesiwaju", "Iwajowa", "Kajola", "Lagelu", "Ogbomosho North", "Ogbomosho South", "Ogo Oluwa", "Olorunsogo", "Oluyole", "Ona Ara", "Orelope", "Ori Ire", "Oyo East", "Oyo West", "Saki East", "Saki West", "Surulere"],
    "Plateau": ["Barkin Ladi", "Bassa", "Bokkos", "Jos East", "Jos North", "Jos South", "Kanam", "Kanke", "Langtang North", "Langtang South", "Mangu", "Mikang", "Pankshin", "Qua'an Pan", "Riyom", "Shendam", "Wase"],
    "Rivers": ["Abua/Odual", "Ahoada East", "Ahoada West", "Akuku-Toru", "Andoni", "Asari-Toru", "Bonny", "Degema", "Eleme", "Emohua", "Etche", "Gokana", "Ikwerre", "Khana", "Obio/Akpor", "Ogba/Egbema/Ndoni", "Ogu/Bolo", "Okrika", "Omuma", "Opobo/Nkoro", "Oyigbo", "Port Harcourt", "Tai"],
    "Sokoto": ["Binji", "Bodinga", "Dange Shuni", "Gada", "Goronyo", "Gudu", "Gwadabawa", "Illela", "Isa", "Kebbe", "Kware", "Rabah", "Sabon Birni", "Shagari", "Silame", "Sokoto North", "Sokoto South", "Tambuwal", "Tangaza", "Tureta", "Wamako", "Wurno", "Yabo"],
    "Taraba": ["Ardo Kola", "Bali", "Donga", "Gashaka", "Gassol", "Ibi", "Jalingo", "Karim Lamido", "Kumi", "Lau", "Sardauna", "Takum", "Ussa", "Wukari", "Yorro", "Zing"],
    "Yobe": ["Bade", "Bursari", "Damaturu", "Fika", "Fune", "Geidam", "Gujba", "Gulani", "Jakusko", "Karasuwa", "Machina", "Nangere", "Nguru", "Potiskum", "Tarmuwa", "Yunusari", "Yusufari"],
    "Zamfara": ["Anka", "Bakura", "Birnin Magaji/Kiyaw", "Bukkuyum", "Bungudu", "Gummi", "Gusau", "Kaura Namoda", "Maradun", "Maru", "Shinkafi", "Talata Mafara", "Chafe", "Zurmi"],
    "FCT": ["Abaji", "Bwari", "Gwagwalada", "Kuje", "Kwali", "Municipal"],
};
const stateSelect = document.getElementById('state');
const lgaSelect = document.getElementById('lga');
if (stateSelect && lgaSelect) {
    stateSelect.addEventListener('change', function() {
        const state = this.value;
        lgaSelect.innerHTML = '<option value="">Select LGA</option>';
        if (stateLgas[state]) {
            stateLgas[state].forEach(lga => {
                const option = document.createElement('option');
                option.value = lga;
                option.textContent = lga;
                lgaSelect.appendChild(option);
            });
        }
    });
}

// Client-side validation for file uploads
const form = document.querySelector('form');
const photoInput = document.getElementById('photo');
const certInput = document.getElementById('certificate');

form.addEventListener('submit', function(e) {
    // Remove any previous error message
    let oldMsg = document.querySelector('.form-message.error.client');
    if (oldMsg) oldMsg.remove();

    // Validate photo
    const photo = photoInput.files[0];
    if (!photo || !['image/jpeg','image/png','image/jpg'].includes(photo.type) || photo.size > 2 * 1024 * 1024) {
        showClientError('Passport photo must be JPG or PNG and not more than 2MB.');
        e.preventDefault();
        return;
    }
    // Validate certificate
    const cert = certInput.files[0];
    if (!cert || !['application/pdf','image/jpeg','image/png','image/jpg'].includes(cert.type) || cert.size > 5 * 1024 * 1024) {
        showClientError('Certificate must be PDF, JPG, or PNG and not more than 5MB.');
        e.preventDefault();
        return;
    }
});

function showClientError(msg) {
    const container = document.querySelector('.container');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-message error client';
    errorDiv.textContent = msg;
    container.insertBefore(errorDiv, container.querySelector('form'));
}
</script>
</body>
</html> 