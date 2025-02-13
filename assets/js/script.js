// Smooth scroll to About Us section
document.querySelector('nav ul li a[href="#about-us"]').addEventListener('click', function (e) {
    e.preventDefault();
    document.querySelector('#about-us').scrollIntoView({ behavior: 'smooth' });
});
