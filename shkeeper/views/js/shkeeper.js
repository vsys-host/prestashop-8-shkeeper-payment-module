$("#get-address").on('click', function () {
    // catch post URL
    const postURL = document.getElementById("post-url")?.innerText;

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
                $('#wallet-address').text(json.wallet);
                $('#amount').empty()
                    .append(document.createTextNode(json.amount + ' '))
                    .append($('<strong>').text(json.display_name));

                // update inputs
                $('input[name=wallet_address]').val(json.wallet)
                $('input[name=wallet_amount]').val(json.amount)

                // show address and amount elements
                $('.pay-container').removeAttr('hidden')

                // Generate the QR as inline SVG. 
                if (typeof qrcode !== 'undefined') {
                    var qr = qrcode(0, 'H');
                    qr.addData(json.wallet + '?amount=' + json.amount);
                    qr.make();
                    // cellSize 4, 16px (4-module) quiet zone so wallet apps scan reliably
                    document.getElementById('qrcode').innerHTML = qr.createSvgTag({ cellSize: 4, margin: 16 });
                }
            }
        }
    });
});

// Copy-to-clipboard for the wallet address shown on the customer order page.
$(document).on('click', '.shk-copy', function () {
    var input = document.getElementById($(this).data('target'));
    if (!input) {
        return;
    }
    input.select();
    try {
        navigator.clipboard.writeText(input.value);
    } catch (e) {
        document.execCommand('copy');
    }
});