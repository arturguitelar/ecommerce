<?php if(!class_exists('Rain\Tpl')){exit;}?><div class="footer-top-area">
    <div class="zigzag-bottom"></div>
    <div class="container">
        <div class="row">
            <div class="col-md-4 col-sm-6">
                <div class="footer-about-us">
                    <h2>Hcode Store</h2>
                    <p>Projeto desenvolvido do zero no <a href="https://www.udemy.com/curso-completo-de-php-7/" target="_blank" rel="noopener noreferrer">Curso de PHP 7</a> disponível na plataforma da <a href="http://www.udemy.com/" target="_blank" rel="noopener noreferrer">Udemy</a> e no site do <a href="https://www.html5dev.com.br/curso/curso-completo-de-php-7" target="_blank" rel="noopener noreferrer">HTML5dev.com.br</a>.</p>
                    <div class="footer-social">
                        <a href="https://www.facebook.com/" target="_blank"><i class="fa fa-facebook"></i></a>
                        <a href="https://twitter.com/" target="_blank"><i class="fa fa-twitter"></i></a>
                        <a href="https://www.youtube.com/" target="_blank"><i class="fa fa-youtube"></i></a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-6">
                <div class="footer-menu">
                    <h2 class="footer-wid-title">Navegação </h2>
                    <ul>
                        <?php if( checkLogin(false) ){ ?>
                        <li><a href="/profile">Minha Conta</a></li>
                        <li><a href="/profile/orders">Meus Pedidos</a></li>
                        <li><a href="/logout"><i class="fa fa-close"></i> Sair</a></li>
                        <?php }else{ ?>
                        <li><a href="/login"><i class="fa fa-lock"></i> Entrar</a></li>
                        <?php } ?>
                    </ul>                        
                </div>
            </div>
            
            <div class="col-md-4 col-sm-6">
                <div class="footer-menu">
                    <h2 class="footer-wid-title">Categorias</h2>
                    <ul>
                        <?php require $this->checkTemplate("categories-menu");?>
                    </ul>                        
                </div>
            </div>
        </div>
    </div>
</div> <!-- End footer top area -->

<div class="footer-bottom-area">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <div class="copyright">
                    <p>&copy; 2017 Hcode Treinamentos. <a href="http://www.hcode.com.br" target="_blank">hcode.com.br</a></p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="footer-card-icon">
                    <i class="fa fa-cc-discover"></i>
                    <i class="fa fa-cc-mastercard"></i>
                    <i class="fa fa-cc-paypal"></i>
                    <i class="fa fa-cc-visa"></i>
                </div>
            </div>
        </div>
    </div>
</div> <!-- End footer bottom area -->

<!-- Latest jQuery form server -->
<script src="https://code.jquery.com/jquery.min.js"></script>

<!-- Bootstrap JS form CDN -->
<script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>

<!-- jQuery sticky menu -->
<script src="/res/site/js/owl.carousel.min.js"></script>
<script src="/res/site/js/jquery.sticky.js"></script>

<!-- jQuery easing -->
<script src="/res/site/js/jquery.easing.1.3.min.js"></script>

<!-- Main Script -->
<script src="/res/site/js/main.js"></script>

<!-- Slider -->
<script type="text/javascript" src="/res/site/js/bxslider.min.js"></script>
<script type="text/javascript" src="/res/site/js/script.slider.js"></script>
</body>
</html>