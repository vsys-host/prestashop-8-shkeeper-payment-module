// catch post URL 
const postURL = document.getElementById("post-url").innerText;

$("#get-address").on('click', function () {

    // hide empty address and amount elements
    $('.pay-container').attr('hidden', 'hidden')
    $('.error-container').attr('hidden', 'hidden')

    // catch selected currency
    const currency = $('#shkeeper-currency').val()

    // reset current data
    $('#wallet-address').text('');
    $('#amount').text('');
    $('#qrcode').text('');

    // send ajax request
    $.ajax({
        type: 'POST',
        headers: { "cache-control": "no-cache" },
        url: postURL + '?currency=' + currency,
        data: 'currency=' + currency,
        beforeSend: function () {
            $('#get-address').val('loading...');
        },
        complete: function () {
            $('#get-address').val('Change Cryptocurrency')
        },
        success: function (json) {

            if(json.status === 'error') {
                $('.error-container').removeAttr('hidden');

            } else {
                // add details info to elements
                $('#wallet-address').append('' + json.wallet);
                $('#amount').append('' + json.amount + ' <strong>' + json.display_name + '</strong>');

                // update inputs
                $('input[name=wallet_address]').val(json.wallet)
                $('input[name=wallet_amount]').val(json.amount)

                // show address and amount elements
                $('.pay-container').removeAttr('hidden')

                // Generate QRCode to scan
                new QRCode(document.getElementById("qrcode"), {
                    text: json.wallet + '?amount=' + json.amount,
                    width: 128,
                    height: 128,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        }
    });
});