<?php
session_start();
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// SMTP settings
$smtpHost = 'mail.achtech.org.ng';
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
    $name = clean($_POST['name'] ?? '', $conn);
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ? clean($_POST['email'], $conn) : '';
    $phone = clean($_POST['phone'] ?? '', $conn);
    $subject = clean($_POST['subject'] ?? '', $conn);
    $message = clean($_POST['message'] ?? '', $conn);
    if (!$name || !$email || !$subject || !$message) {
        $_SESSION['form_message'] = ['type' => 'error', 'text' => 'Please fill all required fields with valid data.'];
        header('Location: contact.php');
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO contact_messages (full_name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
    if ($stmt->execute()) {
        // Send acknowledgment email using PHPMailer
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
            $mail->addAddress($email, $name);
            $mail->Subject = "Thank you for contacting Aries College";
            $mail_msg = "Dear $name,\n\nThank you for reaching out to Aries College. We have received your message and will get back to you soon.\n\nBest regards,\nAries College Team";
            $mail->Body = $mail_msg;
            $mail->send();
            $_SESSION['form_message'] = ['type' => 'success', 'text' => 'Thank you for contacting us! We have sent you an acknowledgment email.'];
        } catch (Exception $e) {
            $_SESSION['form_message'] = ['type' => 'error', 'text' => 'Your message was saved, but we could not send an acknowledgment email. Mailer Error: ' . $mail->ErrorInfo];
        }
    } else {
        $_SESSION['form_message'] = ['type' => 'error', 'text' => 'Error: ' . $conn->error];
    }
    $stmt->close();
    $conn->close();
    header('Location: contact.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Aries College of Health Management & Technology</title>
    
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

    <!-- Page Header -->
    <section class="hero" style="padding: 120px 0 60px;">
        <div class="hero-container">
            <div class="text-center">
                <h1>Contact Us</h1>
                <p class="hero-subtitle">Get in touch with us for any inquiries or support</p>
            </div>
        </div>
    </section>

    <!-- Contact Information Section -->
    <section class="section">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h4>Phone Numbers</h4>
                    <p>0901 216 4632</p>
                    <p>0906 436 9657</p>
                    <p>0906 654 0404</p>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h4>Email Address</h4>
                    <p>info@achtech.org.ng</p>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h4>Location</h4>
                    <p>Old Bambo Group of Schools, <br>
Falade Layout, Oluyole Extension, Apata, Ibadan.</p>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4>Office Hours</h4>
                    <p>Monday - Friday: 8:00 AM - 5:00 PM</p>
                    <p>Saturday: 9:00 AM - 2:00 PM</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="section bg-light">
        <div class="container">
            <div class="contact-container">
                <div class="contact-form-section">
                    <h2 class="section-title">Send Us a Message</h2>
                    <p class="section-subtitle">We'd love to hear from you</p>
                    <?php if (isset($_SESSION['form_message'])): ?>
                        <div class="form-message <?php echo $_SESSION['form_message']['type']; ?>">
                            <?php echo htmlspecialchars($_SESSION['form_message']['text']); ?>
                        </div>
                        <?php unset($_SESSION['form_message']); ?>
                    <?php endif; ?>
                    
                    <form action="" method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone">
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">Subject *</label>
                                <select id="subject" name="subject" required>
                                    <option value="">Select Subject</option>
                                    <option value="admission">Admission Inquiry</option>
                                    <option value="programs">Program Information</option>
                                    <option value="fees">Fees & Payment</option>
                                    <option value="general">General Inquiry</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="message">Message *</label>
                                <textarea id="message" name="message" rows="5" required placeholder="Please tell us how we can help you..."></textarea>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
                    </form>
                </div>
                
                <div class="contact-info-section">
                    <h3>Our Location</h3>
                    <p>Find us on the map below. We look forward to welcoming you to our campus in Oyo State, Nigeria.</p>
                    <div class="map-wrapper" style="width:100%;height:350px;max-width:100%;margin-top:1rem;overflow:hidden;border-radius:16px;">
                        <iframe width="100%" height="100%" frameborder="0" style="border:0;border-radius:16px;min-height:300px;" allowfullscreen src="https://www.google.com/maps?q=7.374129618901634,3.824550635426423&hl=es;z=16&output=embed"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="section">
        <div class="container">
            <div class="faq-grid">
                <div class="faq-item">
                    <div class="faq-question">
                        What programs do you offer?
                    </div>
                    <div class="faq-answer">
                        <p>We offer accredited programs in Health Information Management, Social Work, Hospitality Management, Medical Store Management, and more. Visit our Courses page for a full list.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        How do I apply?
                    </div>
                    <div class="faq-answer">
                        <p>You can apply online through our website or visit our campus. The application process includes filling out forms, submitting required documents, and payment of application fees. Visit our Apply Now page for detailed instructions.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        How much is the application fee?
                    </div>
                    <div class="faq-answer">
                        <p>The application fee is ₦10,230 (Ten Thousand Two Hundred and Thirty Naira). This fee is non-refundable and covers the cost of processing your application.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        Do you offer financial assistance?
                    </div>
                    <div class="faq-answer">
                        <p>We offer various payment plans and may have scholarship opportunities for outstanding students. Please contact our admission office for more information about financial assistance options.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        When do classes start?
                    </div>
                    <div class="faq-answer">
                        <p>Classes typically start in August for the new academic session. Orientation programs are usually held a week before classes begin to help new students settle in.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        Can I visit the campus?
                    </div>
                    <div class="faq-answer">
                        <p>Yes, you're welcome to visit our campus during office hours. We recommend calling ahead to schedule a guided tour and meet with our admission team.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Start Your Journey?</h2>
            <p>Contact us today to learn more about our programs and begin your application</p>
            <div class="hero-buttons">
                <a href="apply.php" class="btn btn-primary btn-lg">Apply Now</a>
                <a href="courses.html" class="btn btn-outline btn-lg">View Courses</a>
            </div>
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

    <!-- Custom JS -->
    <script src="js/main.js"></script>
</body>
</html> 