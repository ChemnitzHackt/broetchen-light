let loggedIn = false
let sessionid = null
const loginMask = $('#loginMask')
const mainMask = $('#mainMask')
const productsList = $('#products')
const preisFormatter = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' })
const gesamtPreis = $('table tfoot .gesamt')

function flashSuccess(msg) {
    const box = $('#flashSuccess')
    box.html(msg).fadeIn()
    window.setTimeout(() => box.fadeOut(), 2000)
}
function flashError(msg) {
    const box = $('#flashError')
    box.html(msg).fadeIn()
    window.setTimeout(() => box.fadeOut(), 2000)
}

loginMask.show()
loginMask.submit((e) => {
    e.preventDefault()
    e.stopPropagation()

    const email_address = $('input[name=email]').val()
    const password = $('input[name=password]').val()
    $.post('/api/login', JSON.stringify({email_address, password}))
        .then((data) => {
            if (data.sessionid) {
                sessionid = data.sessionid
                loggedIn = true
                loginMask.hide()
                mainMask.show()
                flashSuccess("Erfolgreich angemeldet.")
                loadOrders()
            } else {
                flashError("E-Mail oder Passwort falsch.")
            }
        })
        .fail((err) => {
            // console.error(err)
            flashError("E-Mail oder Passwort falsch. ")
        })
})

$('#btnLogout').click(() => {
    // einfach neuladen, Session wird nicht gehalten
    location.reload()
})

//    const services = []
//    const products = []

gesamtPreis.on('update', () => {
    let gesamtkosten = 0
    productsList.find('.kosten').each((idx, el) => {
        gesamtkosten+= $(el).data('value')
    })
    gesamtPreis.data('value', gesamtkosten)
    gesamtPreis.html(preisFormatter.format(gesamtkosten))
})

$.get('/api/services')
    .then((data) => {
        productsList.empty()
        data[0].products.forEach((el) => {
//            console.log(el)

            const stueckpreis = preisFormatter.format(el.price)
            const product = $(`
                    <tr data-name="${el.name}">
                        <td>${el.name} zu je ${stueckpreis}</td>
                        <td><input type="number" class="anzahl" size="3" value=""></td>
                        <td class="nowrap"><span class="kosten"></span></td>
                    </tr>`)
            const anzahl = product.find('.anzahl')
            anzahl.val("0")
            const kosten = product.find('.kosten')
            anzahl.change(() => {
                if (anzahl.val() < 0) anzahl.val(0)
//              console.log("kosten", kosten, anzahl, parseInt(anzahl.val()), el.price)
                const preis = parseInt(anzahl.val()) * el.price
                const preis_gerundet = Math.round(preis * 100, 2) / 100
                kosten.html(preisFormatter.format(preis_gerundet))
                kosten.data('value', preis_gerundet)
                gesamtPreis.trigger('update')
            })
            anzahl.trigger('change')
            anzahl.on('keyup', () => anzahl.trigger('change'))

            productsList.append(product)
        })
    })
    .fail((err) => {
        // console.error(err)
        flashError("Fehler beim lesen der Produkte.")
    })

function loadOrders() {
    $.get('/api/orders/'+sessionid)
        .then((data) => {
//          console.log("orders", data)
            productsList.find('tr[data-name]').each((idx, el) => {
                const product = $(el)
//            console.log("order tr", el, product)
                const name = product.data('name')
                if (data.broetchen[name]) {
//                    console.log("set order", name, data.broetchen[name])
                    const anzahl = data.broetchen[name].anzahl
                    product.find('.anzahl').val(anzahl).trigger('change')
                }
            })
        })
        .fail((err) => {
            // console.error(err)
            flashError("Es liegt noch keine Bestellung vor.")
        })

}

$('#btnBestellen').click(() => {
    orders = {}
    productsList.find('tr[data-name]').each((idx, el) => {
        const product = $(el)
        const name = product.data('name')
        const anzahl = product.find('.anzahl').val()
        const kosten = product.find('.kosten').data('value')
        orders[name] = {anzahl, kosten}
    })
    orders.gesamt = gesamtPreis.data('value')
    const payload = {
        sessionid,
        orders: {
            broetchen: orders
        }
    }
//      console.log("set order", payload)
    $.post('/api/orders', JSON.stringify(payload))
        .then(() => {
            flashSuccess("Es wurde eine E-Mail mit Ihrer Bestellungsaktualisierung versendet.")
        })
        .fail((err) => {
            // console.error(err)
            flashError("Fehler beim Speichern der Bestellung.")
        })

})


$(function () {
    $('[data-toggle="tooltip"]').tooltip()
})