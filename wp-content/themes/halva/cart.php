<?php 
/*
*Template Name: wm basket
*/
?>
<?php get_header(); ?>
            <div class="wrapper">
                <div class="search sc-faq">
                    <a href="">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/catalog.png" alt="">
                        Каталог товаров
                    </a>
                    <?php get_search_form(); ?>
                </div>
            </div>
            <div class="wrapper">
                <div class="want"> 
                    <div class="item-navigation">
                        <ul>
                            <li>Корзина</li>
                        </ul>
                    </div>
                </div>
            </div>   
            <div class="basket-navigation">
                    <div class="wrapper">
                        <div class="inside-basket-nav">
                            <ul>
                                <li>
                                    <a href="" class="active">
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/basket.png" alt="">
                                        Корзина
                                    </a>
                                </li>
                                <li>
                                    <a href="">
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/truck.png" alt="">
                                        Доставка
                                    </a>
                                </li>
                                <li>
                                    <a href="">
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/wallet.png" alt="">
                                        Оплата
                                    </a>
                                </li>
                                <li>
                                    <a href="">
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/g.png" alt="">
                                        Оформление
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>    
            </div>        
            <div class="basket-main">
                <div class="wrapper">
                    <div class="inside-basket-main">
                        <div class="basket-left">

                            <?php get_template_part('woocommerce/cart/cart'); ?>

                            <div class="basket-info">
                                <p>Для того чтобы процесс оформления покупки был ещё проще, <br>
                                    вы можете «Войти под Вашей учетной записью» или «Зарегистрироваться»</p>
                                <a href="" class="log-in">
                                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/user.png" alt="">    
                                    Вход / Регистрация
                                </a>
                            </div>
                        </div>
                        <div class="basket-right">
                            <div class="price-basket">
                                <p class="sum-price-p">Стоимость товаров</p>
                                <p class="final-price"><?php wc_cart_totals_subtotal_html(); ?> р.</p>
                            </div>
                            <div class="delivery-method">
                                <p class="dm-p">Способ доставки</p>
                                <p class="d-method-main">Не выбран</p>
                                <?php echo wm_get_shipping_methods(); ?>
                            </div>
                            <div class="pay-method">
                                <p class="dm-p">Способ оплаты</p>
                                <p class="p-method-main">Не выбран</p>
                            </div>
                            <div class="basket-next">
                                <ul>
                                    <li>Для продолжения введите <br> номер телефона:</li>
                                    <li>
                                        <form action="">
                                            <input type="text" placeholder="+7">
                                            <button>Далее</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<script src="<?php echo get_template_directory_uri() . '/assets/js/wm_catr_page.js' ?>"></script>
<?php get_footer(); ?>