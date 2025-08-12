<?php
/**
 * Plugin Name: Elettrica Bike Customizations
 * Description: Personalizzazione sito elettrica-bike.it
 * Version: 1.7.3
 * Author: Julio Cesar Plascencia
 */


function getProductPrice($product)
{
    if (get_option('woocommerce_tax_display_shop') == 'incl') {
        $price = wc_get_price_including_tax($product);
    } else {
        $price = wc_get_price_excluding_tax($product);
    }
    if (!is_numeric($price)) {
        $price = 0;
    }
    return (int) round($price * 100);
}


function loadProductWidget($html, $product)
{   
	if (get_queried_object_id() != $product->get_id()) {
		return $html;
	}
	
    $importo = (int) getProductPrice($product);

	//<div class="col col-12 price-message d-flex align-items-center justify-content-start"><img width="100" src="https://www.nencinisport.it/upload/landing_pages/finanziamenti/findomestic.png" height="30" class="pe-2"><span class=""><p class="mb-0">Possibilità di Finanziamento </p><p class="mb-0"><a class="findomestic-calcola-rata" href="https://secure.findomestic.it/clienti/pmcrs/nencini/mcommerce/pages/simulatore.html?prf=13936&amp;tvei=1003605011&amp;Importo=59990&amp;versione=L" target="_blank">Calcola rata </a></p></span></div>
     //
     $html .= '<div class="col col-12" style="font-size:14px !important;padding:0px !important"><img width="100" src="https://www.nencinisport.it/upload/landing_pages/finanziamenti/findomestic.png" height="30" class="pe-2"> Possibilità di finanziamento fino a 48 rate. <a href="https://secure.findomestic.it/clienti/pmcrs/elettricabike/mcommerce/pages/simulatore.html?prf=13740&tvei=1004223889&Importo='.$importo.'&versione=L" target="_blank">Calcola rata</a></div>';
 
     return $html;
}

add_filter('woocommerce_get_price_html', 'loadProductWidget', 10, 2);



// Add a message below the price on the single product page
add_action('woocommerce_single_product_summary', 'add_text_below_price', 10.3);
function add_text_below_price() {
    echo do_shortcode('[wcb2brestrictedcontent allowed="121885"]<p style="margin-top: 5px; font-size: 14px;">*Il prezzo visualizzato è escluso IVA</p>[/wcb2brestrictedcontent]');
}


 function custom_featured_label_script() {
    ?>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            var socialLoginDivs = document.querySelectorAll('.wd-social-login');
            if (window.location.href.includes('b2b-login')) {
                socialLoginDivs.forEach(function(div) {
                    div.style.display = 'none';
                });
            }

            var dividers = document.querySelectorAll('p.title.wd-login-divider');
            if (window.location.href.includes('b2b-login')) {
                dividers.forEach(function(div) {
                    div.style.display = 'none';
                });
            }

            var element = document.querySelector('.featured.product-label');
            if (element) {
                element.textContent = 'PIU VENDUTO'; 
            }
            var element = document.querySelector('.wd-reset-var');
            if (element) {
                element.style.display = 'none';
            }
            
            var element = document.querySelector('.woocommerce-variation.single_variation.wd-show');
            if (element) {
                element.classList.remove('wd-show');
                element.style.display = 'none';
                element.style.visibility = 'hidden';
                element.style.opacity = '0';
            }

            var label = document.querySelector('label[for="pa_size"]');
            if(label){
                var tr = label.closest('tr');
                if (tr) {
                    tr.style.display = 'none';
                }
            }

            var count = 0;
            function checkAndProcessElement() {
                var stockElement = document.querySelector('p.stock.in-stock.wd-style-default');
                if (stockElement) {
                    

                    var stockText = stockElement.textContent || stockElement.innerText;
                    var matches = stockText.match(/\d+/); // Trova i numeri nel testo

                    if (matches) {
                        count = count + 1;
                        /*
                        if(count == 2){
                            clearInterval(intervalId); // Ferma il controllo periodico
                        }
                        */

                        var stockNumber = parseInt(matches[0], 10); // Converti il testo trovato in un numero

                        if (stockNumber > 2) {
                            console.log('Disponibile');
                            stockElement.textContent = 'Disponibile';
                            stockElement.innerText = 'Disponibile';
                        } else if (stockNumber > 0) {
                            console.log('In esaurimento');
                            stockElement.textContent = 'In esaurimento';
                            stockElement.innerText = 'In esaurimento';
                        }
                    }
                }
            }

            // Imposta un intervallo per controllare l'elemento ogni 0.5 secondi
            var intervalId = setInterval(checkAndProcessElement, 10);
            
        });
    </script>
    <?php
}
add_action('wp_footer', 'custom_featured_label_script');

function custom_product_meta_content() {
    global $product; // Make the $product object available

    // Ensure that the product object is not null
    if ( ! is_a( $product, 'WC_Product' ) ) {
        $product = wc_get_product( get_the_ID() );
    }

    // Get the SKU from the product object
    $sku = $product->get_sku();

    // Output the custom HTML with the SKU
    echo '<p><b>Codice Prodotto:</b> ' . $sku . '</p>';
}

remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
add_action('woocommerce_single_product_summary', 'custom_product_meta_content', 40);

add_action('woocommerce_cart_calculate_fees', 'apply_bank_transfer_discount');
function apply_bank_transfer_discount() {
    if (is_admin() && !defined('DOING_AJAX')) return;

    // Metodo di pagamento selezionato
    $payment_method = WC()->session->get('chosen_payment_method');

    // Verifica se il metodo selezionato è "bacs" (bonifico bancario in WooCommerce)
    if ($payment_method === 'bacs') {
        $cart_total = WC()->cart->get_cart_contents_total();
        $discount = $cart_total * 0.02; // 2% di sconto
        WC()->cart->add_fee(__('Sconto Bonifico Bancario (2%)', 'woocommerce'), -$discount);
    }
}



add_action('wp_footer', 'refresh_cart_on_payment_method_change');
function refresh_cart_on_payment_method_change() {
    if (is_checkout()) {
        ?>
        <script type="text/javascript">
        jQuery(function($){
            $('form.checkout').on('change', 'input[name="payment_method"]', function(){
                $('body').trigger('update_checkout');
            });
        });
        </script>
        <?php
    }
}

add_action('woocommerce_before_checkout_process', function() {
    if (WC()->cart->total <= 0) {
        wc_add_notice(__('Error: Order total must be greater than zero.'), 'error');
    }
});



?>