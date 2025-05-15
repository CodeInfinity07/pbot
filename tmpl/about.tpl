{include file="header.tpl"}
<section class="banner clear__top bg__img" data-background="assets/images/banner/banner-bg.png" style="background-image: url(&quot;assets/images/banner/banner-bg.png&quot;);">
        <div class="container">
            <div class="banner__area">
                <h1 class="neutral-top">About Us</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="home">Home</a>
                        </li>
                       
                        <li class="breadcrumb-item active" aria-current="page">
                            About Us
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </section>
    <section class="about__overview">
        <div class="video video--secondary">
            <div class="container">
                <div class="video__area">
                    <img src="assets/images/about-video-illustration.png" alt="Video Illustration">
                    <div class="video__btn">
                        <a class="video__popup" href="https://www.youtube.com/watch?v=LCihLrSehCo" target="_blank" title="YouTube video player">
                            <i class="fa-solid fa-play"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="about__overview__area">
                <div class="section__header">
                    <h5 class="neutral-top">Investing in real estate is now easier than buying stocks</h5>
                    <h2>Make property Investing in simple,
                        accessible and transparent</h2>
                    <p class="neutral-bottom">Our mission is to empower the world to build wealth through modern real
                        estate investing.</p>
                </div>
                <div class="portfolio__overview">
                    <div class="row">
                    <div class="col-md-6 col-lg-4">
                        <div class="portfolio__overview__single column__space">
                            <img src="assets/images/icons/investors.png" alt="Investors">
                            <div>
                                <h2 class="counterTwo">{$settings.info_box_total_accounts_generated}</h2>
                                <p>Investors</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="portfolio__overview__single column__space">
                            <img src="assets/images/icons/completed.png" alt="completed">
                            <div>
                                <h2 class=""> {$currency_sign}{$settings.info_box_deposit_funds_generated}</h2>
                                <p>Investments Completed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 col-lg-4">
                        <div class="portfolio__overview__single">
                            <img src="assets/images/icons/annual-return.png" alt="Average Annual Return">
                            <div>
                                <h2><span class="counterTwo">144</span>%</h2>
                                <p>Contract Return</p>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </section>
    
    <section class="market market--two market--three section__space__bottom">
        <div class="container">
            <div class="market__area market__area--two section__space bg__img" data-background="assets/images/light-two.png" style="background-image: url(&quot;assets/images/light-two.png&quot;);">
                <div class="row d-flex align-items-center">
                    <div class="col-lg-6">
                        <div class="content">
                            <h5 class="neutral-top">Real exposure to the real estate market</h5>
                            <h2>You Invest. Restate
                                Does the Rest</h2>
                            <p>Transparent Real Estate Investing Through Restate.Join us and
                                experience a smarter,better way to invest in real estate</p>
                            <a href="properties" class="button button--effect">Start Exploring</a>
                            <img src="assets/images/arrow.png" alt="Go to">
                        </div>
                    </div>
                </div>
                <img src="assets/images/market-two-illustration.png" alt="Explore the Market" class="d-none d-lg-block market__two__thumb">
            </div>
            <div class="market__features">
                <div class="row">
                    <div class="col-md-6 col-xl-4">
                        <div class="market__features__single shadow__effect__secondary">
                            <img src="assets/images/icons/gain.png" alt="Gain Instant">
                            <h4>Gain Instant</h4>
                            <p class="neutral-bottom">Restate performs deep due diligence on sponsors, giving investors
                                peace of mind.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-4">
                        <div class="market__features__single market__features__single--alt shadow__effect">
                            <img src="assets/images/icons/noticed.png" alt="Get noticed">
                            <h4>Get Noticed</h4>
                            <p class="neutral-bottom">Restate VERIFIED sponsor profiles are available to accredited real
                                estate investment
                                investors.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-4">
                        <div class="market__features__single alt shadow__effect__secondary">
                            <img src="assets/images/icons/focus.png" alt="Focus on Deals">
                            <h4>Focus on Deals</h4>
                            <p class="neutral-bottom">Spend less time smiling, reaserching and dialing and more time
                                doing what you love.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>
    
    {include file="footer.tpl"}