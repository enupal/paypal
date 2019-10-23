(function($) {
    /**
     * EnupalPaypal class
     */
    var EnupalPaypal = Garnish.Base.extend({

        options: null,
        $sizeSelect: null,
        $languageSelect: null,
        $unlimitedStock: null,

        /**
         * The constructor.
         */
        init: function() {
            // init method
            this.$sizeSelect = $("#fields-size");
            this.$languageSelect = $("#fields-language");
            this.$unlimitedStock = $("#fields-unlimited-stock");
            this.$currencySelect = $("#fields-currency");

            this.changeOptions();
            this.addListener(this.$sizeSelect, 'change', 'changeOptions');
            this.addListener(this.$languageSelect, 'change', 'changeOptions');
            this.addListener(this.$unlimitedStock, 'change', 'handleUnlimitedStock');
            this.addListener(this.$currencySelect, 'change', 'handleCurrencySelect');
        },

        changeOptions: function()
        {
            var value = this.$sizeSelect.val();
            var language = this.$languageSelect.val();
            var data = {'size': value, 'language': language};

            if (value < 7){
                $("#fields-preview-wrapper").removeClass('hidden');
                $("#fields-custom-size-url").addClass('hidden');
                $("#fields-custom-size-class").addClass('hidden');
                Craft.postActionRequest('enupal-paypal/settings/get-size-url', data, $.proxy(function(response, textStatus)
                {
                    var statusSuccess = (textStatus === 'success');

                    if(statusSuccess && response.buttonUrl)
                    {
                        $("#fields-button-preview").attr("src",response.buttonUrl);
                    }
                    else
                    {
                        Craft.cp.displayError(Craft.t('enupal-paypal','An unknown error occurred.'));
                    }
                }, this));
            }else{
                if (value == 7){
                    $("#fields-preview-wrapper").addClass('hidden');
                    $("#fields-custom-size-class").addClass('hidden');
                    $("#fields-custom-size-url").removeClass('hidden');
                }else if(value == 8){
                    $("#fields-preview-wrapper").addClass('hidden');
                    $("#fields-custom-size-class").removeClass('hidden');
                    $("#fields-custom-size-url").addClass('hidden');
                }
            }


        },

        handleUnlimitedStock: function(option) {
            var $checkbox = $(option.currentTarget),
                $text = $checkbox.parent().prevAll('.textwrapper:first').children('.text:first');

            if ($checkbox.prop('checked')) {
                $text.prop('disabled', true).addClass('disabled').val('');
            }
            else {
                $text.prop('disabled', false).removeClass('disabled').focus();
            }
            
        },

        handleCurrencySelect: function() {
            var value = this.$currencySelect.val();
            var $shippingDiv = $("#fields-shippingAmount-field").find(".label, .light");

            $shippingDiv.text(value);
        }

    });

    window.EnupalPaypal = EnupalPaypal;

})(jQuery);