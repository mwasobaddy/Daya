import React, { useState, useEffect } from 'react';
import {
    BarChart3,
    Users,
    CheckSquare,
    TrendingUp,
    Rocket,
    Music,
    HandshakeIcon,
    QrCode,
    Play,
    Menu,
    X,
    ArrowRight,
} from 'lucide-react';

const styles = `
    :root {
        --turquoise: #7AC4DB;
        --cobalt: #005EA3;
        --navy: #292175;
        --lime: #7DBF30;
        --yellow: #DEB81A;
        --dark: #1F1A17;
        --white: #FFFFFF;
        --light-bg: #F8FAFD;
        --light-gray: #F5F8FB;
        --transition: all 0.3s ease;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    html {
        scroll-behavior: smooth;
    }

    body {
        font-family: 'Source Sans Pro', sans-serif;
        font-weight: 400;
        color: var(--dark);
        background: var(--white);
        line-height: 1.6;
        overflow-x: hidden;
    }

    h1, h2, h3, h4 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 600;
        line-height: 1.3;
    }

    .container {
        width: 85%;
        max-width: 1200px;
        margin: 0 auto;
    }

    header {
        position: sticky;
        top: 0;
        background: var(--white);
        padding: 1.2rem 0;
        z-index: 1000;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        transition: var(--transition);
    }

    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo-container {
        display: flex;
        align-items: center;
    }

    .logo-img {
        height: 42px;
        width: auto;
        transition: var(--transition);
    }

    .nav-links {
        display: flex;
        gap: 2.5rem;
        align-items: center;
    }

    .nav-links a {
        text-decoration: none;
        color: var(--dark);
        font-weight: 500;
        font-size: 1rem;
        position: relative;
        padding: 0.5rem 0;
        transition: var(--transition);
    }

    .nav-links a:hover {
        color: var(--cobalt);
    }

    .nav-links a::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background: var(--turquoise);
        transition: width 0.3s ease;
    }

    .nav-links a:hover::after {
        width: 100%;
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--turquoise), var(--cobalt));
        color: white;
        padding: 0.9rem 2rem;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 700;
        font-size: 1rem;
        transition: transform 0.2s, box-shadow 0.2s;
        border: none;
        cursor: pointer;
        gap: 0.5rem;
        font-family: 'Montserrat', sans-serif;
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(122, 196, 219, 0.35);
    }

    .btn-primary:active {
        transform: translateY(-1px);
    }

    .nav-btn {
        color: white !important;
        font-weight: 700 !important;
        padding: 0.8rem 1.8rem !important;
        background: linear-gradient(135deg, var(--turquoise), var(--cobalt));
        border-radius: 30px;
        transition: all 0.3s ease;
    }

    .nav-btn:hover {
        color: white !important;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(122, 196, 219, 0.35);
    }

    .nav-btn::after {
        display: none !important;
    }

    .menu-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--dark);
        cursor: pointer;
    }

    .hero {
        background: linear-gradient(135deg, var(--turquoise), var(--cobalt));
        color: white;
        padding: 5rem 0 4rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: 
            radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.1) 2px, transparent 2px),
            radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 2px, transparent 2px);
        background-size: 100px 100px;
        opacity: 0.3;
    }

    .hero h1 {
        font-size: 3rem;
        margin-bottom: 1.2rem;
        position: relative;
        z-index: 1;
    }

    .hero p {
        font-size: 1.2rem;
        margin-bottom: 2.5rem;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        opacity: 0.95;
        position: relative;
        z-index: 1;
        font-weight: 300;
    }

    .hero-buttons {
        display: flex;
        justify-content: center;
        margin-bottom: 3rem;
        position: relative;
        z-index: 1;
    }

    .video-wrapper {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
        overflow: hidden;
        border-radius: 16px;
        margin: 0 auto;
        max-width: 800px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        transition: var(--transition);
        background: var(--dark);
        border: 3px solid white;
    }

    .video-wrapper:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
    }

    .video-wrapper iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
    }

    .video-placeholder {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, var(--navy), var(--cobalt));
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: white;
        z-index: 1;
    }

    .video-placeholder svg {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: var(--turquoise);
    }

    .stats {
        padding: 3.5rem 0;
        background: var(--light-gray);
    }

    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
        text-align: center;
    }

    .stat-item {
        padding: 1.5rem;
    }

    .stat-item h3 {
        font-size: 2.5rem;
        color: var(--cobalt);
        margin-bottom: 0.5rem;
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
    }

    .stat-item p {
        color: var(--navy);
        font-weight: 500;
    }

    .section {
        padding: 4.5rem 0;
    }

    .section-title {
        text-align: center;
        margin-bottom: 3rem;
        position: relative;
    }

    .section-title h2 {
        font-size: 2.2rem;
        color: var(--navy);
        margin-bottom: 0.8rem;
        display: inline-block;
    }

    .section-title h2::after {
        content: '';
        display: block;
        width: 60px;
        height: 4px;
        background: var(--lime);
        margin: 0.8rem auto;
        border-radius: 2px;
    }

    .section-title p {
        max-width: 700px;
        margin: 0 auto;
        color: #555;
        font-size: 1.1rem;
        font-weight: 300;
    }

    .bg-light {
        background: var(--light-bg);
    }

    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.8rem;
    }

    .card {
        background: white;
        padding: 2rem;
        border-radius: 16px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
        transition: transform 0.4s ease, box-shadow 0.4s ease;
        border-top: 4px solid transparent;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }

    .card-icon {
        font-size: 2.2rem;
        margin-bottom: 1.2rem;
        color: var(--cobalt);
    }

    .card h3 {
        font-size: 1.3rem;
        margin-bottom: 0.8rem;
        color: var(--navy);
        font-family: 'Montserrat', sans-serif;
    }

    .card p {
        color: #555;
        margin-bottom: 1rem;
        font-weight: 300;
    }

    .testimonials {
        padding: 4.5rem 0;
    }

    .testimonial-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
    }

    .testimonial {
        background: white;
        padding: 2rem;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
        position: relative;
        border: 2px solid rgba(122, 196, 219, 0.1);
    }

    .testimonial::before {
        content: '"';
        position: absolute;
        top: -15px;
        left: 20px;
        font-size: 5rem;
        color: var(--turquoise);
        opacity: 0.2;
        font-family: Georgia, serif;
    }

    .testimonial-text {
        margin-bottom: 1.5rem;
        font-style: italic;
        color: #444;
        line-height: 1.6;
        font-weight: 300;
    }

    .testimonial-author {
        display: flex;
        align-items: center;
    }

    .author-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--turquoise), var(--cobalt));
        margin-right: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-family: 'Montserrat', sans-serif;
    }

    .author-info h4 {
        margin-bottom: 0.2rem;
        color: var(--navy);
        font-family: 'Montserrat', sans-serif;
    }

    .author-info p {
        color: #666;
        font-size: 0.9rem;
        font-weight: 300;
    }

    .cta {
        padding: 5rem 0;
        text-align: center;
        background: linear-gradient(135deg, var(--navy), var(--cobalt));
        color: white;
        position: relative;
        overflow: hidden;
    }

    .cta::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: 
            radial-gradient(circle at 10% 90%, rgba(255, 255, 255, 0.1) 2px, transparent 2px),
            radial-gradient(circle at 90% 10%, rgba(255, 255, 255, 0.1) 2px, transparent 2px);
        background-size: 80px 80px;
        opacity: 0.3;
    }

    .cta h2 {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        position: relative;
        z-index: 1;
    }

    .cta p {
        font-size: 1.2rem;
        margin-bottom: 2.5rem;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        opacity: 0.9;
        position: relative;
        z-index: 1;
        font-weight: 300;
    }

    footer {
        background: var(--navy);
        color: white;
        padding: 3rem 0 2rem;
    }

    .footer-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2.5rem;
        margin-bottom: 2.5rem;
    }

    .footer-logo {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .footer-logo-img {
        height: 36px;
        width: auto;
        filter: brightness(0) invert(1);
    }

    .footer-section h4 {
        margin-bottom: 1.2rem;
        color: white;
        font-size: 1.1rem;
        font-family: 'Montserrat', sans-serif;
    }

    .footer-links {
        list-style: none;
    }

    .footer-links li {
        margin-bottom: 0.6rem;
    }

    .footer-links a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: var(--transition);
        font-weight: 300;
    }

    .footer-links a:hover {
        color: var(--turquoise);
        padding-left: 5px;
    }

    .footer-links svg {
        margin-right: 0.5rem;
        width: 20px;
    }

    .footer-bottom {
        text-align: center;
        padding-top: 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.9rem;
        font-weight: 300;
    }

    .footer-bottom strong {
        font-weight: 600;
        color: white;
    }

    .color-accent {
        position: absolute;
        border-radius: 50%;
        opacity: 0.1;
        z-index: 0;
    }

    .color-accent-1 {
        width: 300px;
        height: 300px;
        background: var(--turquoise);
        top: -150px;
        right: -150px;
    }

    .color-accent-2 {
        width: 200px;
        height: 200px;
        background: var(--lime);
        bottom: -100px;
        left: -100px;
    }

    .fade-in {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.6s ease, transform 0.6s ease;
    }

    .fade-in.visible {
        opacity: 1;
        transform: translateY(0);
    }

    @media (max-width: 992px) {
        .hero h1 {
            font-size: 2.5rem;
        }
        
        .section {
            padding: 3.5rem 0;
        }
    }

    @media (max-width: 768px) {
        .container {
            width: 90%;
        }
        
        .nav-links {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: white;
            flex-direction: column;
            padding: 1.5rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            gap: 1rem;
        }
        
        .nav-links.active {
            display: flex;
        }
        
        .menu-toggle {
            display: block;
        }
        
        .hero h1 {
            font-size: 2.2rem;
        }
        
        .hero p {
            font-size: 1.1rem;
        }
        
        .hero-buttons {
            flex-direction: column;
            align-items: center;
        }
        
        .hero-buttons a {
            width: 100%;
            max-width: 300px;
        }
        
        .footer-container {
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        
        .nav-btn {
            margin-top: 0.5rem;
            text-align: center;
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .hero {
            padding: 4rem 0 3rem;
        }
        
        .hero h1 {
            font-size: 1.9rem;
        }
        
        .section-title h2 {
            font-size: 1.8rem;
        }
        
        .stat-item h3 {
            font-size: 2rem;
        }
        
        .cta h2 {
            font-size: 2rem;
        }
        
        .btn-primary {
            padding: 0.8rem 1.5rem;
            font-size: 0.95rem;
        }
        
        .nav-btn {
            padding: 0.7rem 1.5rem !important;
        }
        
        .logo-img {
            height: 36px;
        }
    }
`;

export default function DDSHome() {
    const [menuOpen, setMenuOpen] = useState(false);
    const [scrollY, setScrollY] = useState(0);

    useEffect(() => {
        const handleScroll = () => setScrollY(window.scrollY);
        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    useEffect(() => {
        const elements = document.querySelectorAll('.fade-in');
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            },
            { threshold: 0.1, rootMargin: '0px 0px -50px 0px' }
        );

        elements.forEach((el) => observer.observe(el));
        return () => observer.disconnect();
    }, []);

    return (
        <>
            <style>{styles}</style>
            
            {/* Header */}
            <header style={{ 
                padding: scrollY > 50 ? '0.8rem 0' : '1.2rem 0',
                boxShadow: scrollY > 50 ? '0 4px 20px rgba(0, 0, 0, 0.1)' : '0 4px 20px rgba(0, 0, 0, 0.05)'
            }}>
                <div className="container header-container">
                    <div className="logo-container">
                        <img
                            src="https://daya.africa/wp-content/uploads/2024/10/cropped-Daya-Main-Logo.png"
                            alt="Daya Logo"
                            className="logo-img"
                        />
                    </div>

                    <button
                        className="menu-toggle"
                        onClick={() => setMenuOpen(!menuOpen)}
                        aria-label="Toggle menu"
                    >
                        {menuOpen ? <X size={24} /> : <Menu size={24} />}
                    </button>

                    <nav className={`nav-links ${menuOpen ? 'active' : ''}`}>
                        <a href="#who-its-for" onClick={() => setMenuOpen(false)}>Who it's for</a>
                        <a href="#testimonials" onClick={() => setMenuOpen(false)}>Testimonials</a>
                        <a href="/welcome" className="btn-primary nav-btn" onClick={() => setMenuOpen(false)}>
                            Get Started <ArrowRight size={20} />
                        </a>
                    </nav>
                </div>
            </header>

            {/* Hero Section */}
            <section className="hero">
                <div className="color-accent color-accent-1"></div>
                <div className="color-accent color-accent-2"></div>
                <div className="container fade-in">
                    <h1>Find your first users, fans, and responses</h1>
                    <p>Community-led distribution across Kenya — from hyperlocal to national. Connect with real people where they are.</p>

                    <div className="hero-buttons">
                        <a href="/welcome" className="btn-primary">
                            Get Started <Rocket size={20} />
                        </a>
                    </div>

                    <div className="video-wrapper">
                        <div className="video-placeholder" id="videoPlaceholder">
                            <Play size={48} />
                            <p>Loading video...</p>
                        </div>
                        <iframe
                            src="https://www.youtube.com/embed/V_oDGl1hm5o?rel=0&modestbranding=1"
                            title="Daya Distribution Video"
                            frameBorder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowFullScreen
                            loading="lazy"
                            onLoad={() => {
                                const placeholder = document.getElementById('videoPlaceholder');
                                if (placeholder) placeholder.style.display = 'none';
                            }}
                        ></iframe>
                    </div>
                </div>
            </section>

            {/* Stats Section */}
            <section className="stats">
                <div className="container stats-container">
                    <div className="stat-item fade-in">
                        <h3>10,000+</h3>
                        <p>Community Activations</p>
                    </div>
                    <div className="stat-item fade-in">
                        <h3>85%</h3>
                        <p>Verified Completion Rate</p>
                    </div>
                    <div className="stat-item fade-in">
                        <h3>50+</h3>
                        <p>Towns Covered</p>
                    </div>
                    <div className="stat-item fade-in">
                        <h3>200+</h3>
                        <p>Trusted Distributors</p>
                    </div>
                </div>
            </section>

            {/* Built for early traction */}
            <section className="section container fade-in">
                <div className="section-title">
                    <h2>Built for early traction</h2>
                    <p>Daya Distribution helps startups, creators, and organisations find real adoption — not just impressions.</p>
                </div>

                <div className="cards">
                    <div className="card" style={{ borderTopColor: 'var(--turquoise)' }}>
                        <div className="card-icon">
                            <BarChart3 size={32} />
                        </div>
                        <h3>First app downloads</h3>
                        <p>Get genuine users for your app from offline communities across Kenya.</p>
                    </div>

                    <div className="card" style={{ borderTopColor: 'var(--cobalt)' }}>
                        <div className="card-icon">
                            <Users size={32} />
                        </div>
                        <h3>First fans & listeners</h3>
                        <p>Build an authentic audience for your music, podcast, or content.</p>
                    </div>

                    <div className="card" style={{ borderTopColor: 'var(--lime)' }}>
                        <div className="card-icon">
                            <CheckSquare size={32} />
                        </div>
                        <h3>Community surveys</h3>
                        <p>Collect reliable data and insights directly from target communities.</p>
                    </div>

                    <div className="card" style={{ borderTopColor: 'var(--yellow)' }}>
                        <div className="card-icon">
                            <TrendingUp size={32} />
                        </div>
                        <h3>Demand validation</h3>
                        <p>Test your product or service with real people before scaling.</p>
                    </div>
                </div>
            </section>

            {/* Who it's for */}
            <section className="section bg-light" id="who-its-for">
                <div className="container fade-in">
                    <div className="section-title">
                        <h2>Who it's for</h2>
                        <p>Daya Distribution serves a diverse range of innovators building for Kenyan communities.</p>
                    </div>

                    <div className="cards">
                        <div className="card" style={{ borderTopColor: 'var(--cobalt)' }}>
                            <div className="card-icon">
                                <Rocket size={32} />
                            </div>
                            <h3>Startups</h3>
                            <p>Validate demand and acquire your first 1000 users with measurable results.</p>
                        </div>

                        <div className="card" style={{ borderTopColor: 'var(--lime)' }}>
                            <div className="card-icon">
                                <Music size={32} />
                            </div>
                            <h3>Musicians & Creators</h3>
                            <p>Build loyal fans and listeners beyond digital platforms.</p>
                        </div>

                        <div className="card" style={{ borderTopColor: 'var(--turquoise)' }}>
                            <div className="card-icon">
                                <HandshakeIcon size={32} />
                            </div>
                            <h3>NGOs & Researchers</h3>
                            <p>Run community surveys and collect reliable field data.</p>
                        </div>

                        <div className="card" style={{ borderTopColor: 'var(--yellow)' }}>
                            <div className="card-icon">
                                <QrCode size={32} />
                            </div>
                            <h3>Apps & Services</h3>
                            <p>Reach offline communities through QR-enabled physical spaces.</p>
                        </div>
                    </div>
                </div>
            </section>

            {/* Testimonials */}
            <section className="testimonials" id="testimonials">
                <div className="container fade-in">
                    <div className="section-title">
                        <h2>Trusted by Kenyan Innovators</h2>
                        <p>See what our partners say about working with Daya Distribution.</p>
                    </div>

                    <div className="testimonial-cards">
                        <div className="testimonial">
                            <div className="testimonial-text">
                                "Daya helped us validate our farming app with real smallholder farmers in Western Kenya. We got 500+ genuine downloads and invaluable feedback in just two weeks."
                            </div>
                            <div className="testimonial-author">
                                <div className="author-avatar">JK</div>
                                <div className="author-info">
                                    <h4>James Kariuki</h4>
                                    <p>Co-founder, AgriTech Solutions</p>
                                </div>
                            </div>
                        </div>

                        <div className="testimonial">
                            <div className="testimonial-text">
                                "As an independent musician, building a fanbase was challenging. Daya helped me reach listeners in 15 towns across Kenya. My streams increased by 300%!"
                            </div>
                            <div className="testimonial-author">
                                <div className="author-avatar">NM</div>
                                <div className="author-info">
                                    <h4>Naomi Mwangi</h4>
                                    <p>Independent Artist</p>
                                </div>
                            </div>
                        </div>

                        <div className="testimonial">
                            <div className="testimonial-text">
                                "We needed reliable survey data from rural communities for our research. Daya provided verified responses from 8 counties with transparent methodology."
                            </div>
                            <div className="testimonial-author">
                                <div className="author-avatar">PO</div>
                                <div className="author-info">
                                    <h4>Public Health Organization</h4>
                                    <p>Research Division</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* CTA Section */}
            <section className="cta" id="cta">
                <div className="container fade-in">
                    <h2>Ready to find your first users?</h2>
                    <p>Join hundreds of innovators who have found authentic adoption through community-led distribution.</p>
                    <a href="/campaign/submit" className="btn-primary">
                        Start Your Campaign <ArrowRight size={20} />
                    </a>
                </div>
            </section>

            {/* Footer */}
            <footer>
                <div className="container">
                    <div className="footer-container">
                        <div className="footer-section">
                            <div className="footer-logo">
                                <img
                                    src="https://daya.africa/wp-content/uploads/2024/10/cropped-Daya-Main-Logo.png"
                                    alt="Daya Logo"
                                    className="footer-logo-img"
                                />
                            </div>
                            <p>Community-led distribution across Kenya — from hyperlocal to national.</p>
                        </div>

                        <div className="footer-section">
                            <h4>Product</h4>
                            <ul className="footer-links">
                                <li><a href="#who-its-for">Who it's for</a></li>
                                <li><a href="#testimonials">Testimonials</a></li>
                                <li><a href="#cta">Get Started</a></li>
                            </ul>
                        </div>

                        <div className="footer-section">
                            <h4>Company</h4>
                            <ul className="footer-links">
                                <li><a href="#">About Daya</a></li>
                                <li><a href="#">Blog</a></li>
                                <li><a href="#">Careers</a></li>
                                <li><a href="#">Contact</a></li>
                            </ul>
                        </div>

                        <div className="footer-section">
                            <h4>Connect</h4>
                            <ul className="footer-links">
                                <li><a href="#">Twitter</a></li>
                                <li><a href="#">LinkedIn</a></li>
                                <li><a href="#">Instagram</a></li>
                                <li><a href="#">Email</a></li>
                            </ul>
                        </div>
                    </div>

                    <div className="footer-bottom">
                        <p><strong>Daya Distribution</strong> — Part of the Daya ecosystem</p>
                        <p>© Daya Africa 2025. All rights reserved.</p>
                    </div>
                </div>
            </footer>
        </>
    );
}
