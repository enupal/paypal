(function($) {
    /**
     * EnupalPaypal class
     */
    var EnupalPaypal = Garnish.Base.extend({

        options: null,
        $sizeSelect: null,
        $languageSelect: null,

        /**
         * The constructor.
         */
        init: function() {
            // init method
            this.$sizeSelect = $("#fields-size");
            this.$languageSelect = $("#fields-language");
            this.changeOptions();
            this.addListener(this.$sizeSelect, 'change', 'changeOptions');
            this.addListener(this.$languageSelect, 'change', 'changeOptions');
        },

        changeOptions: function()
        {
            var value = this.$sizeSelect.val();
            var language = this.$languageSelect.val();
            var data = {'size': value, 'language': language};

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
        },

    });

    window.EnupalPaypal = EnupalPaypal;

})(jQuery);