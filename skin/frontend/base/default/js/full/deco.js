console.log('hehe');
Review.addMethods({
    nextStep: function(transport){
        if (transport) {
            var response = transport.responseJSON || transport.responseText.evalJSON(true) || {};

            if (response.redirect) {
                this.isSuccess = true;
                location.href = encodeURI(response.redirect);
                return;
            }
            if (response.success) {
                this.isSuccess = true;
                location.href = encodeURI(this.successUrl);
            }
            else{
                alert('hoho it is working');
                var msg = response.error_messages;
                if (Object.isArray(msg)) {
                    msg = msg.join("\n").stripTags().toString();
                }
                if (msg) {
                    alert(msg);
                }
            }

            if (response.update_section) {
                $('checkout-'+response.update_section.name+'-load').update(response.update_section.html);
            }

            if (response.goto_section) {
                checkout.gotoSection(response.goto_section, true);
            }
        }
    }
});