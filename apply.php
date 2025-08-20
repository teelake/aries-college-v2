<?php
session_start();
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
    .payment-info {
        margin-bottom: 1.5rem;
    }
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 8px;
        border-left: 4px solid;
        margin-bottom: 1rem;
    }
    .alert-info {
        background: #dbeafe;
        color: #1e40af;
        border-left-color: #3b82f6;
    }
    .alert i {
        margin-right: 0.5rem;
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
            <form id="applicationForm" action="process_application.php" method="POST" enctype="multipart/form-data">
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
                            <label for="resultStatus">Result Status *</label>
                            <select id="resultStatus" name="resultStatus" required>
                                <option value="">Select Result Status</option>
                                <option value="available">Available</option>
                                <option value="awaiting_result">Awaiting Result</option>
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
                <option value="Medical Laboratory Technician">Medical Laboratory Technician</option>
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
                        <div class="form-group" id="certificateGroup">
                            <label for="certificate">Certificate (PDF/JPG/PNG) <span id="certificateRequired">*</span></label>
                            <input type="file" id="certificate" name="certificate" accept=".pdf,image/*">
                            <small id="certificateNote" style="color: #6b7280; font-size: 0.875rem;">Upload your certificate or result</small>
                        </div>
                    </div>
                    <div class="payment-info">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Application Fee:</strong> ₦10,230 (Ten Thousand Two Hundred and Thirty Naira)
                            <br>
                            <small>Your application will only be processed after successful payment.</small>
                        </div>
                    </div>
                    <div class="form-navigation">
                        <button type="button" class="btn btn-outline prev-step">Previous</button>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-credit-card"></i> Submit Application & Pay ₦10,230
                        </button>
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
// Nigerian States and LGAs mapping (only if not already declared)
if (typeof stateLgas === 'undefined') {
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
}

// Client-side validation and form submission
const form = document.querySelector('form');
const photoInput = document.getElementById('photo');
const certInput = document.getElementById('certificate');
const resultStatusSelect = document.getElementById('resultStatus');
const certificateGroup = document.getElementById('certificateGroup');
const certificateRequired = document.getElementById('certificateRequired');
const certificateNote = document.getElementById('certificateNote');

// Handle result status change
if (resultStatusSelect) {
    resultStatusSelect.addEventListener('change', function() {
        const status = this.value;
        if (status === 'awaiting_result') {
            certInput.removeAttribute('required');
            certificateRequired.textContent = '';
            certificateNote.textContent = 'Certificate upload is optional when awaiting result';
            certificateNote.style.color = '#059669';
        } else {
            certInput.setAttribute('required', 'required');
            certificateRequired.textContent = '*';
            certificateNote.textContent = 'Upload your certificate or result';
            certificateNote.style.color = '#6b7280';
        }
    });
}

form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Remove any previous error message
    let oldMsg = document.querySelector('.form-message.error.client');
    if (oldMsg) oldMsg.remove();

    // Validate photo
    const photo = photoInput.files[0];
    if (!photo || !['image/jpeg','image/png','image/jpg'].includes(photo.type) || photo.size > 2 * 1024 * 1024) {
        showClientError('Passport photo must be JPG or PNG and not more than 2MB.');
        return;
    }
    
    // Validate certificate (only if result status is not awaiting_result)
    const resultStatus = resultStatusSelect ? resultStatusSelect.value : 'available';
    if (resultStatus !== 'awaiting_result') {
        const cert = certInput.files[0];
        if (!cert || !['application/pdf','image/jpeg','image/png','image/jpg'].includes(cert.type) || cert.size > 5 * 1024 * 1024) {
            showClientError('Certificate must be PDF, JPG, or PNG and not more than 5MB.');
            return;
        }
    }
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    submitBtn.disabled = true;
    
    // Submit form via AJAX
    const formData = new FormData(form);
    
    fetch('process_application.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
                    // Show success message immediately
                    showMessage('Application submitted successfully! Redirecting to payment...', 'success');
                    
                    // Store payment reference for later use
                    if (data.data && data.data.reference) {
                        sessionStorage.setItem('payment_reference', data.data.reference);
                    }
                    
                    // Redirect to payment immediately
                    if (data.data && data.data.payment_url) {
                window.location.href = data.data.payment_url;
                    } else {
                        showMessage('Payment URL not received. Please try again.', 'error');
                    }
        } else {
                    showMessage(data.message || 'An error occurred. Please try again.', 'error');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showClientError('Network error: ' + error.message);
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

function showClientError(message) {
    const errorDiv = document.getElementById('error-message');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        errorDiv.scrollIntoView({ behavior: 'smooth' });
    }
}
    
    function showMessage(message, type = 'info') {
        // Remove any existing message
        const existingMessage = document.querySelector('.alert-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Create new message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `alert alert-${type} alert-message`;
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: 600;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        
        // Set background color based on type
        if (type === 'success') {
            messageDiv.style.backgroundColor = '#10b981';
        } else if (type === 'error') {
            messageDiv.style.backgroundColor = '#ef4444';
        } else {
            messageDiv.style.backgroundColor = '#3b82f6';
        }
        
        messageDiv.textContent = message;
        document.body.appendChild(messageDiv);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
}
</script>
</body>
</html> 