<?php
// Start session
session_start();

// Check if user is logged in - menerima kedua format sesi
$is_logged_in = (isset($_SESSION['user_id']) && isset($_SESSION['user_logged_in'])) || 
               (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']));

// Ambil nama user - menerima kedua format sesi
$user_name = '';
if ($is_logged_in) {
    $user_name = isset($_SESSION['user_nama']) ? $_SESSION['user_nama'] : 
                (isset($_SESSION['nama']) ? $_SESSION['nama'] : '');
}

// Cek apakah user adalah admin
$is_admin = $is_logged_in && isset($_SESSION['is_admin']);
?>
<!doctype html>
<html class="no-js" lang="zxx">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Freedom Fitness Gym</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="site.webmanifest">
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.ico">

    <!-- CSS here -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/owl.carousel.min.css">
    <link rel="stylesheet" href="assets/css/slicknav.css">
    <link rel="stylesheet" href="assets/css/flaticon.css">
    <link rel="stylesheet" href="assets/css/gijgo.css">
    <link rel="stylesheet" href="assets/css/animate.min.css">
    <link rel="stylesheet" href="assets/css/animated-headline.css">
    <link rel="stylesheet" href="assets/css/magnific-popup.css">
    <link rel="stylesheet" href="assets/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/themify-icons.css">
    <link rel="stylesheet" href="assets/css/slick.css">
    <link rel="stylesheet" href="assets/css/nice-select.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .slider-area {
        margin-top: -1px;
        background-image: url(assets/img/hero/h1_hero.jpg);
        background-repeat: no-repeat;
        background-position: top center;
        background-size: cover;
        background-attachment: fixed;
        background-color: rgba(0, 0, 0, 0.7);
            background-blend-mode: overlay;
            min-height: 100vh;
        }
        .welcome-user {
            color: #fff;
            margin-right: 15px;
            font-weight: 700; /* Bold font */
            font-size: 15px; /* Ukuran font yang sama dengan navbar */
            text-transform: uppercase; /* Sesuai dengan gaya navbar */
            font-family: 'Montserrat', sans-serif; /* Font yang sama dengan navbar */
            letter-spacing: 0.5px; /* Jarak antar huruf */
            display: inline-flex;
            align-items: center;
        }
        
        .logout-btn {
            background-color: transparent;
            border: 1px solid #dc3545;
            color: #dc3545;
            border-radius: 30px;
            padding: 10px 20px;
            transition: all 0.3s ease;
            text-decoration: none;
            margin-left: 10px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background-color: #dc3545;
            color: white;
        }
        
        .admin-btn {
            background-color: #ffc107;
            border: 1px solid #ffc107;
            color: #212529;
            border-radius: 30px;
            padding: 10px 20px;
            transition: all 0.3s ease;
            text-decoration: none;
            margin-left: 10px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
        }
        
        .admin-btn:hover {
            background-color: #e0a800;
            border-color: #e0a800;
            color: #212529;
        }
    </style>
</head>
<body class="black-bg">
    <!-- ? Preloader Start -->
    <div id="preloader-active">
        <div class="preloader d-flex align-items-center justify-content-center">
            <div class="preloader-inner position-relative">
                <div class="preloader-circle"></div>
                <div class="preloader-img pere-text">
                    <img src="img/TT.png" alt="">
                </div>
            </div>
        </div>
    </div>
    <!-- Preloader Start -->
    <header>
        <!-- Header Start -->
        <div class="header-area header-transparent">
            <div class="main-header header-sticky">
                <div class="container-fluid">
                    <div class="menu-wrapper d-flex align-items-center justify-content-between">
                        <!-- Logo -->
                        <div class="logo">
                            <a href="index.php"><img src="img/TT.png" alt=""></a>
                        </div>
                        <!-- Main-menu -->
                        <div class="main-menu f-right d-none d-lg-block">
                            <nav>
                                <ul id="navigation">
                                    <li><a href="index.php">Home</a></li>
                                    <li><a href="pricing.php">List Harga</a></li>
                                    <li><a href="cekMembership.php">Membership</a></li>
                                    <li><a href="bookClass.php">Pesan Kelas</a></li>
                                </ul>
                            </nav>
                        </div>          
                        <!-- Header-btn -->
                        <div class="header-btns d-none d-lg-block f-right">
                            <?php if($is_logged_in): ?>
                                <span class="welcome-user">Halo, <?php echo htmlspecialchars($user_name); ?></span>
                                <?php if($is_admin): ?>
                                    <a href="admin-dashboard.php" class="admin-btn">Admin Panel</a>
                                <?php endif; ?>
                                <a href="logout.php" class="logout-btn">Logout</a>
                            <?php else: ?>
                                <a href="login.php" class="btn">LOGIN</a>
                                <a href="admin-login.php" class="btn">Admin</a>
                            <?php endif; ?>
                        </div>
                       <!-- Mobile Menu -->
                       <div class="col-12">
                        <div class="mobile_menu d-block d-lg-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Header End -->
</header>
<main>
    <!--? slider Area Start-->
<div class="slider-area position-relative">
    <div class="slider-active">
        <!-- Single Slider -->
        <div class="single-slider slider-height d-flex align-items-center">
            <div class="container">
                <div class="row">
                    <div class="col-xl-9 col-lg-9 col-md-10">
                        <div class="hero__caption">
                            <span data-animation="fadeInLeft" data-delay="0.1s">WELCOME TO</span>
                            <h1 data-animation="fadeInLeft" data-delay="0.4s">FREEDOM FITNESS GYM</h1>
                                <?php if($is_logged_in): ?>
                                <!-- Tombol untuk user yang sudah login -->
                                <a href="cekMembership.php" class="border-btn hero-btn" data-animation="fadeInLeft" data-delay="0.8s">CEK MEMBERSHIP</a>
                                <?php else: ?>
                                <!-- Tombol untuk user yang belum login -->
                                <a href="register.php" class="border-btn hero-btn" data-animation="fadeInLeft" data-delay="0.8s">DAFTAR DISINI</a>
                                <?php endif; ?>
                                <br>
                            </div>
                        </div>
                    </div>
                </div>          
            </div>
        </div>
    </div>
    <!-- slider Area End-->
    
    <!-- Traning categories Start -->
    <section class="traning-categories black-bg">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-6 col-lg-6">
                    <div class="single-topic text-center mb-30">
                        <div class="topic-img">
                            <img src="assets/img/gallery/cat1.png" alt="">
                            <div class="topic-content-box">
                                <div class="topic-content">
                                    <h3>Latihan Personal</h3>
                                    <p>Dapatkan pengalaman latihan yang personal dengan instruktur profesional kami. <br> Program ini dirancang khusus untuk membantu mencapai tujuan kebugaran Anda.</p>
                                    <a href="gallery.html" class="border-btn">Lihat Gambar</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6">
                    <div class="single-topic text-center mb-30">
                        <div class="topic-img">
                            <img src="assets/img/gallery/cat2.png" alt="">
                            <div class="topic-content-box">
                                <div class="topic-content">
                                    <h3>Latihan Kelompok</h3>
                                    <p>Bergabunglah dengan kelas latihan kelompok yang seru dan energik bersama member lainnya. <br> Pilih dari berbagai kelas yang kami tawarkan untuk pengalaman fitness yang lebih menyenangkan.</p>
                                    <a href="gallery.html" class="btn">Lihat Gambar</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Traning categories End-->
    <!--? Team -->
    <section class="team-area fix">
        <div class="container">
            <div class="row">
                <div class="col-xl-12">
                    <div class="section-tittle text-center mb-55 wow fadeInUp" data-wow-duration="1s" data-wow-delay=".1s">
                        <h2 >ADA APA DI GYM INI?</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="single-cat text-center mb-30 wow fadeInUp" data-wow-duration="1s" data-wow-delay=".2s" >
                        <div class="cat-icon">
                            <img src="assets/img/gallery/team1.png" alt="">
                        </div>
                        <div class="cat-cap">
                            <h5><a href="services.html">Body Building</a></h5>
                            <p>Program khusus untuk membangun massa otot dengan kombinasi latihan yang tepat dan nutrisi yang seimbang</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="single-cat text-center mb-30 wow fadeInUp" data-wow-duration="1s" data-wow-delay=".4s">
                        <div class="cat-icon">
                            <img src="assets/img/gallery/team2.png" alt="">
                        </div>
                        <div class="cat-cap">
                            <h5><a href="services.html">Pembentukan Otot</a></h5>
                            <p>Program yang berfokus pada pembentukan dan definisi otot dengan panduan dari instruktur profesional kami</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="single-cat text-center mb-30 wow fadeInUp" data-wow-duration="1s" data-wow-delay=".6s">
                        <div class="cat-icon">
                            <img src="assets/img/gallery/team3.png" alt="">
                        </div>
                        <div class="cat-cap">
                            <h5><a href="services.html">Penurunan Berat Badan</a></h5>
                            <p>Program efektif untuk menurunkan berat badan dengan kombinasi kardio, latihan beban, dan saran nutrisi</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Services End -->
    <!--? Gallery Area Start -->
    <div class="gallery-area section-padding30 ">
        <div class="container-fluid ">
            <div class="row">
                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6">
                    <div class="box snake mb-30">
                        <div class="gallery-img big-img" style="background-image: url(assets/img/gallery/gallery1.png);"></div>
                        <div class="overlay">
                            <div class="overlay-content">
                                <h3>Latihan Pembentukan Otot</h3>
                                <a href="gallery.html"><i class="ti-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6">
                    <div class="box snake mb-30">
                        <div class="gallery-img big-img" style="background-image: url(assets/img/gallery/gallery2.png);"></div>
                        <div class="overlay">
                            <div class="overlay-content">
                                <h3>Kelas Kardio Intensif</h3>
                                <a href="gallery.html"><i class="ti-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6">
                    <div class="box snake mb-30">
                        <div class="gallery-img big-img" style="background-image: url(assets/img/gallery/gallery3.png);"></div>
                        <div class="overlay">
                            <div class="overlay-content">
                                <h3>Program Pelatihan Kekuatan</h3>
                                <a href="gallery.html"><i class="ti-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
                    <div class="box snake mb-30">
                        <div class="gallery-img big-img" style="background-image: url(assets/img/gallery/gallery4.png);"></div>
                        <div class="overlay">
                            <div class="overlay-content">
                                <h3>Kelas Yoga & Fleksibilitas</h3>
                                <a href="gallery.html"><i class="ti-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
                    <div class="box snake mb-30">
                        <div class="gallery-img big-img" style="background-image: url(assets/img/gallery/gallery5.png);"></div>
                        <div class="overlay">
                            <div class="overlay-content">
                                <h3>Latihan Beban Terbimbing</h3>
                                <a href="gallery.html"><i class="ti-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6">
                    <div class="box snake mb-30">
                        <div class="gallery-img big-img" style="background-image: url(assets/img/gallery/gallery6.png);"></div>
                        <div class="overlay">
                            <div class="overlay-content">
                                <h3>Ruang Fitness Lengkap</h3>
                                <a href="gallery.html"><i class="ti-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Gallery Area End -->
   
    <!--? About Area-2 Start -->
    <section class="about-area2 fix pb-padding pt-50 pb-80">
        <div class="support-wrapper align-items-center">
            <div class="right-content2">
                <!-- img -->
                <div class="right-img wow fadeInUp" data-wow-duration="1s" data-wow-delay=".1s">
                    <img src="assets/img/gallery/about.png" alt="">
                </div>
            </div>
            <div class="left-content2">
                <!-- section tittle -->
                <div class="section-tittle2 mb-20 wow fadeInUp" data-wow-duration="1s" data-wow-delay=".3s">
                    <div class="front-text">
                        <h2 class="">Tentang Kami</h2>
                        <p>Freedom Gym adalah pusat kebugaran modern yang menyediakan berbagai peralatan fitness berkualitas tinggi dan kelas latihan yang dipimpin oleh instruktur berpengalaman. Kami berkomitmen untuk membantu Anda mencapai tujuan kebugaran Anda.</p>
                        <p class="mb-40">Dengan fasilitas lengkap dan program latihan yang disesuaikan dengan kebutuhan individu, kami menciptakan lingkungan yang mendukung untuk semua level kebugaran, dari pemula hingga atlet profesional.</p>
                        <a href="gallery.html" class="border-btn">Kelas Kami</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- About Area End -->
    <!--? Blog Area Start -->
    <section class="home-blog-area pt-10 pb-50">
        <div class="container">
            <!-- Section Tittle -->
            <div class="row justify-content-center">
                <div class="col-lg-7 col-md-9 col-sm-10">
                    <div class="section-tittle text-center mb-100 wow fadeInUp" data-wow-duration="2s" data-wow-delay=".2s">
                        <h2>Artikel Fitness</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-6 col-lg-6 col-md-6">
                    <div class="home-blog-single mb-30 wow fadeInUp" data-wow-duration="1s" data-wow-delay=".4s">
                        <div class="blog-img-cap">
                            <div class="blog-img">
                                <img src="assets/img/gallery/blog1.png" alt="">
                            </div>
                            <div class="blog-cap">
                                <span>Gym & Fitness</span>
                                <h3><a href="">Tips Latihan Efektif untuk Pemula di Gym</a></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6 col-md-6">
                    <div class="home-blog-single mb-30 wow fadeInUp" data-wow-duration="2s" data-wow-delay=".6s">
                        <div class="blog-img-cap">
                            <div class="blog-img">
                                <img src="assets/img/gallery/blog2.png" alt="">
                            </div>
                            <div class="blog-cap">
                                <span>Gym & Fitness</span>
                                <h3><a href="">Nutrisi Optimal untuk Memaksimalkan Hasil Latihan Anda</a></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Blog Area End -->
    <!--? video_start -->
    <div class="video-area section-bg2 d-flex align-items-center"  data-background="assets/img/gallery/video-bg.png">
        <div class="container">
            <div class="video-wrap position-relative">
                <div class="video-icon" >
                    <a class="popup-video btn-icon" href="https://youtu.be/bJpn4GWfSTQ"><i class="fas fa-play"></i></a>
                </div>
            </div>
        </div>
    </div>
    <!-- video_end -->
   <!-- ? services-area -->
   <section class="services-area">
        <div class="container">
            <div class="row justify-content-between">
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-8">
                    <div class="single-services mb-40">
                        <div class="features-icon">
                            <img src="assets/img/icon/icon1.svg" alt="">
                        </div>
                        <div class="features-caption">
                            <h3>Lokasi</h3>
                            <p>Universitas Pembangunan Nasional "Veteran" Yogyakarta Kampus 2 Babarsari </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-8">
                    <div class="single-services mb-40">
                        <div class="features-icon">
                            <img src="assets/img/icon/icon2.svg" alt="">
                        </div>
                        <div class="features-caption">
                            <h3>Telepon</h3>
                            <p>0821-2204-8502</p>
                            <p>0896-6849-2188</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-8">
                    <div class="single-services mb-40">
                        <div class="features-icon">
                            <img src="assets/img/icon/icon3.svg" alt="">
                        </div>
                        <div class="features-caption">
                            <h3>Email</h3>
                            <p>dzakywiratamadzaky@gmail.com</p>
                            <p>hafidyunna@gmail.com</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<footer>
    <!--? Footer Start-->
    <div class="footer-area black-bg">
        <div class="container">
            <div class="footer-top footer-padding">
                <!-- Footer Menu -->
                <div class="row">
                    <div class="col-xl-12">
                        <div class="single-footer-caption mb-50 text-center">
                            <!-- logo -->
                            <div class="footer-logo wow fadeInUp" data-wow-duration="1s" data-wow-delay=".2s">
                                <a href="index.html"><img src="img/TT.png" alt=""></a>
                            </div>
                            <!-- Menu -->
                            <!-- Header Start -->
                            <div class="header-area main-header2 wow fadeInUp" data-wow-duration="2s" data-wow-delay=".4s">
                                <div class="main-header main-header2">
                                    <div class="menu-wrapper menu-wrapper2">
                                        <!-- Main-menu -->
                                        <div class="main-menu main-menu2 text-center">
                                            <nav>
                                                <ul>
                                                    <li><a href="index.php">Home</a></li>
                                                    <li><a href="pricing.php">Pricing</a></li>
                                                    <li><a href="gallery.html">Gallery</a></li>
                                                    <li><a href="cekMembership.php">Membership</a></li>
                                                </ul>
                                            </nav>
                                        </div>   
                                    </div>
                                </div>
                            </div>
                            <!-- Header End -->
                            <!-- social -->
                            <div class="footer-social mt-30 wow fadeInUp" data-wow-duration="3s" data-wow-delay=".8s">
                                <a href="#"><i class="fab fa-youtube"></i></a>
                                <a href="#"><i class="fab fa-facebook-f"></i></a>
                                <a href="https://www.instagram.com/dzaktama/"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="row d-flex align-items-center">
                    <div class="col-lg-12">
                        <div class="footer-copy-right text-center">
                            <p>
                              Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved | <i class="" aria-hidden="true"></i>  <a href="index.php" target="_blank">Projek RBPL Dzaky & Hafid</a>
                            </p>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
      <!-- Footer End-->
  </footer>
  <!-- Scroll Up -->
  <div id="back-top" >
    <a title="Go to Top" href="#"> <i class="fas fa-level-up-alt"></i></a>
</div>

<!-- JS here -->

<script src="./assets/js/vendor/modernizr-3.5.0.min.js"></script>
<!-- Jquery, Popper, Bootstrap -->
<script src="./assets/js/vendor/jquery-1.12.4.min.js"></script>
<script src="./assets/js/popper.min.js"></script>
<script src="./assets/js/bootstrap.min.js"></script>
<!-- Jquery Mobile Menu -->
<script src="./assets/js/jquery.slicknav.min.js"></script>

<!-- Jquery Slick , Owl-Carousel Plugins -->
<script src="./assets/js/owl.carousel.min.js"></script>
<script src="./assets/js/slick.min.js"></script>
<!-- One Page, Animated-HeadLin -->
<script src="./assets/js/wow.min.js"></script>
<script src="./assets/js/animated.headline.js"></script>
<script src="./assets/js/jquery.magnific-popup.js"></script>

<!-- Date Picker -->
<script src="./assets/js/gijgo.min.js"></script>
<!-- Nice-select, sticky -->
<script src="./assets/js/jquery.nice-select.min.js"></script>
<script src="./assets/js/jquery.sticky.js"></script>

<!-- counter , waypoint,Hover Direction -->
<script src="./assets/js/jquery.counterup.min.js"></script>
<script src="./assets/js/waypoints.min.js"></script>
<script src="./assets/js/jquery.countdown.min.js"></script>
<script src="./assets/js/hover-direction-snake.min.js"></script>

<!-- contact js -->
<script src="./assets/js/contact.js"></script>
<script src="./assets/js/jquery.form.js"></script>
<script src="./assets/js/jquery.validate.min.js"></script>
<script src="./assets/js/mail-script.js"></script>
<script src="./assets/js/jquery.ajaxchimp.min.js"></script>

<!-- Jquery Plugins, main Jquery -->	
<script src="./assets/js/plugins.js"></script>
<script src="./assets/js/main.js"></script>

</body>
</html>