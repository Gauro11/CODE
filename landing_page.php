<?php
// PHP code for database connection and data fetching
require_once 'connection.php';

// SQL query to fetch feedback data
// Joining ratings, appointments, and clients tables to get all necessary information
// Limiting to a reasonable number and ordering by most recent for reviews
$sql = "SELECT
            r.id as rating_id,
            r.rating,
            r.feedback,
            r.created_at as feedback_created_at,
            c.first_name,
            c.last_name
        FROM
            ratings r
        LEFT JOIN
            appointments a ON r.appointment_id = a.id
        LEFT JOIN
            clients c ON r.client_id = c.id
        ORDER BY
            r.created_at DESC
        LIMIT 10";

$result = $conn->query($sql);

$feedbacks = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $feedbacks[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ALAZIMA Cleaning Services L.L.C</title>
    <link rel="icon" href="site_icon.png" type="image/png">

    <link rel="stylesheet" href="landing_page.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

</head>

<body>
    <header class="header" id="header">
        <nav class="nav container">
            <a href="#" class="nav__logo">
                <img src="LOGO.png" alt="ALAZIMA Cleaning Services LLC Logo">
            </a>

            <div class="nav__menu" id="nav-menu">
                <ul class="nav__list">
                    <li class="nav__item"><a href="#home" class="nav__link active-link">Home</a></li>
                    <li class="nav__item"><a href="#about" class="nav__link">About</a></li>
                    <li class="nav__item"><a href="#cleaningservices" class="nav__link">Services</a></li>
                    <li class="nav__item"><a href="#contact" class="nav__link">Contact</a></li>
                    <li class="nav__item"><a href="#reviews" class="nav__link">Reviews</a></li>
                </ul>
                <div class="nav__close" id="nav-close">
                    <i class="bx bx-x"></i>
                </div>
            </div>

            <div class="nav__buttons">
                <button class="button--header button--sign-up" onclick="window.location.href='sign_up.php'">SIGN UP</button>
                <button class="button--header button--login" onclick="window.location.href='landing_page2.html'">LOGIN</button>
                <div class="nav__toggle" id="nav-toggle">
                    <i class="bx bx-menu"></i>
                </div>
            </div>
        </nav>
    </header>

    <main class="main">
        <section class="home" id="home">
            <div class="content">
                <h1>Dubai's Premier <br> Deep Cleaning Services</h1>
                <div class="buttons">
                    <button class="button"  onclick="window.location.href='landing_page2.html'">Book Now</button>
                </div>
            </div>
        </section>

        <section class="about section" id="about">
            <div class="container">
                <div class="about__container grid">
                    <div class="about-text">
                        <h3>WE ARE</h3>
                        <h2>YOUR TRUSTED PARTNER FOR SPOTLESS SPACES <br> IN DUBAI </h2>
                        <p>
                            <strong>Our company was founded with a singular aim: </strong>
                            <br>To raise the standard of cleanliness and provide services <br> that consistently exceed expectations.
                            <br>Driven by a passion <br> for excellence, wherein every service is delivered with <br> meticulous attention to detail.
                        </p>
                        <p class="about__quote"> "Where spotless service begins"</p>
                        <br>
                        <h3>MISSION</h3>
                        <p>
                            To be the most trusted and respected cleaning service provider, setting the standard for quality, sustainability, and customer satisfaction in the industry.
                        </p>
                        <p class="about__quote"> "We work with passion, not just to gain profit"
                        </p>
                    </div>
                    <div class="about-img">
                        <img src="About_pic.jpg" alt="Bedroom">
                    </div>
                </div>
            </div>
        </section>

        <section class="services-section" id="cleaningservices">
            <h2 class="section-title">SELECT THE SERVICE THAT SUITS YOU</h2>
            <div class="services-container">
                <div class="service-card">
                    <div class="icon-box">
                        <img src="General.png" alt="General Cleaning Icon">
                    </div>
                    <h3>General Cleaning</h3>
                    <p class="subtitle">For Homes and Offices</p>
                    <p>Standard cleaning service tailored to the number of hours booked</p>
                    <button class="book-now-btn"  onclick="window.location.href='landing_page2.html'">Book Now</button>
                </div>

                <div class="service-card">
                    <div class="icon-box">
                        <img src="Checkout.png" alt="Checkout Cleaning Icon">
                    </div>
                    <h3>Checkout Cleaning</h3>
                    <p class="subtitle">For Holiday Rental Units</p>
                    <p>Designed to prepare the unit for the next guest after checkout</p>
                    <button class="book-now-btn"  onclick="window.location.href='landing_page2.html'">Book Now</button>
                </div>

                <div class="service-card">
                    <div class="icon-box">
                        <img src="In House.png" alt="In-House Cleaning Icon">
                    </div>
                    <h3>In-House Cleaning</h3>
                    <p>Cleaning service requested while the guest is currently staying in the unit</p>
                    <button class="book-now-btn"  onclick="window.location.href='landing_page2.html'">Book Now</button>
                </div>

                <div class="service-card">
                    <div class="icon-box">
                        <img src="Refresh.png" alt="Refresh Cleaning Icon">
                    </div>
                    <h3>Refresh Cleaning</h3>
                    <p>For units that have been vacant for several days or weeks</p>
                    <button class="book-now-btn"  onclick="window.location.href='landing_page2.html'">Book Now</button>
                </div>

                <div class="service-card">
                    <div class="icon-box">
                        <img src="Deep Cleaning.png" alt="Deep Cleaning Icon">
                    </div>
                    <h3>Deep Cleaning</h3>
                    <p>Intensive cleaning service for units in very dirty or "disaster" conditions</p>
                    <button class="book-now-btn"  onclick="window.location.href='landing_page2.html'">Book Now</button>
                </div>
            </div>
        </section>


        <section class="contact section" id="contact">
            <h2 class="contact__title">Experience the clean difference today!</h2>
            <p class="contact__description">
                Our team is available from 9:00 AM to 6:00 PM Daily to assist you and schedule your cleaning with ease.
            </p>

            <div class="contact__container">
                <div class="contact__card--location">
                    <i class="fas fa-map-marker-alt"></i>
                    Trade Center First Al Moosa Tower 2 Office 24-O<br>
                    Dubai, United Arab Emirates
                </div>

                <div class="contact__inline-box">
                    <div class="contact__card--inline">
                        <i class="fas fa-phone-alt"></i> 052-9009188
                    </div>
                    <div class="contact__card--inline">
                        <i class="fas fa-envelope"></i> azimamaids.tg@gmail.com
                    </div>
                </div>
            </div>
        </section>

        <section class="reviews section" id="reviews">
            <h2 class="section-title">Building Trust and Delivering Excellence, <br> One Client Review at a Time</h2>

            <div class="reviews__container swiper"> <div class="swiper-wrapper">
                    <?php if (!empty($feedbacks)): ?>
                        <?php foreach ($feedbacks as $feedback): ?>
                            <div class="review-card swiper-slide">
                                <div class="stars">
                                    <?php
                                    $rating = (int)$feedback['rating'];
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<i class="fas fa-star"></i>'; // Filled star
                                        } else {
                                            echo '<i class="far fa-star"></i>'; // Empty star (assuming 'far' for font-awesome regular star)
                                        }
                                    }
                                    ?>
                                </div>
                                <p class="review-text"><?php echo htmlspecialchars($feedback['feedback']); ?></p>
                                <p class="reviewer-name"><?php echo htmlspecialchars($feedback['first_name'] . ' ' . $feedback['last_name']); ?></p>
                                <p class="reviewer-role">Customer</p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="review-card swiper-slide">
                            <div class="stars">
                                <i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                            </div>
                            <p class="review-text">No reviews available yet. Be the first to leave one!</p>
                            <p class="reviewer-name">Alazima Team</p>
                            <p class="reviewer-role"></p>
                        </div>
                    <?php endif; ?>
                    </div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
                <div class="swiper-pagination"></div>
            </div>
        </section>
        </main>


        <footer class="footer">
            <div class="container">
                <p class="footer__copyright">&copy; 2025 ALAZIMA Cleaning Service. All Rights Reserved.</p>
            </div>
        </footer>


    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <script>
        /*=============== SHOW MENU (Mobile Navigation) ===============*/
        const navMenu = document.getElementById('nav-menu'),
            navToggle = document.getElementById('nav-toggle'),
            navClose = document.getElementById('nav-close')

        /* Menu show */
        if (navToggle) {
            navToggle.addEventListener('click', () => {
                navMenu.classList.add('show-menu')
            })
        }

        /* Menu hidden */
        if (navClose) {
            navClose.addEventListener('click', () => {
                navMenu.classList.remove('show-menu')
            })
        }

        /* REMOVE MENU MOBILE (if clicking a link closes the menu) */
        const navLink = document.querySelectorAll('.nav__link')

        function linkAction() {
            const navMenu = document.getElementById('nav-menu')
            navMenu.classList.remove('show-menu')
        }
        navLink.forEach(n => n.addEventListener('click', linkAction))


        /*=============== HEADER SCROLL BEHAVIOR ===============*/
        let lastScrollY = window.scrollY;
        let hideHeaderTimeout;
        const header = document.getElementById('header');
        const headerHeight = header.offsetHeight;

        function makeHeaderVisible() {
            clearTimeout(hideHeaderTimeout);
            header.classList.remove('header-hidden');
            header.classList.add('header-visible');
            header.style.pointerEvents = 'auto';
        }

        function makeHeaderHidden() {
            if (window.scrollY > headerHeight + 10) {
                header.classList.remove('header-visible');
                header.classList.add('header-hidden');
            }
        }

        window.addEventListener('scroll', () => {
            clearTimeout(hideHeaderTimeout);

            if (window.scrollY > 50) {
                header.classList.add('scroll-header');
            } else {
                header.classList.remove('scroll-header');
            }

            if (window.scrollY > lastScrollY && window.scrollY > headerHeight) {
                makeHeaderVisible();
                hideHeaderTimeout = setTimeout(makeHeaderHidden, 1000);
            } else if (window.scrollY < lastScrollY || window.scrollY <= headerHeight) {
                makeHeaderVisible();
            }

            lastScrollY = window.scrollY;
        });

        header.addEventListener('mouseenter', () => {
            makeHeaderVisible();
        });

        header.addEventListener('mouseleave', () => {
            if (window.scrollY > headerHeight) {
                hideHeaderTimeout = setTimeout(makeHeaderHidden, 500);
            }
        });

        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(() => {
                if (window.scrollY > header.offsetHeight) {
                    hideHeaderTimeout = setTimeout(makeHeaderHidden, 2000);
                } else {
                    makeHeaderVisible();
                }
            }, 100);
        });


        /*=============== ACTIVE LINK SCROLL SECTION ===============*/
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav__link');
        // 'header' is already defined above, so we don't declare it again

        function scrollActive() {
            const scrollY = window.pageYOffset;
            const scrollBottom = window.innerHeight + scrollY;
            const pageHeight = document.body.offsetHeight;
            let currentSectionId = null;

            // Special check: If at the end of the page, force the last section
            if (pageHeight - scrollBottom < 5) {
                const lastSection = sections[sections.length - 1];
                if (lastSection) {
                    currentSectionId = lastSection.getAttribute('id');
                }
            } else {
                // Original logic for all other sections
                sections.forEach(current => {
                    const sectionTop = current.offsetTop - header.offsetHeight - 50;
                    const sectionHeight = current.offsetHeight;
                    const sectionId = current.getAttribute('id');

                    if (scrollY >= sectionTop && scrollY < sectionTop + sectionHeight) {
                        currentSectionId = sectionId;
                    }
                });
            }

            // Logic for updating the active-link (no changes here)
            navLinks.forEach(link => {
                link.classList.remove('active-link');
                if (currentSectionId && link.getAttribute('href').includes(currentSectionId)) {
                    link.classList.add('active-link');
                }
            });
        }

        window.addEventListener('scroll', scrollActive);


        /*=============== SWIPER JS INITIALIZATION ===============*/
        // Initialize Swiper after the DOM is fully loaded
        document.addEventListener("DOMContentLoaded", function() {
            new Swiper(".reviews__container", {
                slidesPerView: 1,
                spaceBetween: 30,
                loop: true,
                centeredSlides: true,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true,
                },
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
                breakpoints: {
                    768: {
                        slidesPerView: 2,
                    },
                    992: {
                        slidesPerView: 3,
                    },
                },
                speed: 800,
            });
        });
    </script>
</body>

</html>