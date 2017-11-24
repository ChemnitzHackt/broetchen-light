let loggedIn = false
let sessionid = null
const loginMask = $('#loginMask')
const mainMask = $('#mainMask')
//    const serviceList = $('#services')
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
    $.post('/api/login', JSON.stringify({email_address, password})).then(
        (data) => {
            if (data.sessionid) {
                sessionid = data.sessionid
                loggedIn = true
                loginMask.hide()
                mainMask.show()

                loadOrders()
            }
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
    gesamtPreis.html(preisFormatter.format(gesamtkosten))
})

$.get('/api/services').then(
    (data) => {
//          console.log("services", data)
//          serviceList.empty()
        productsList.empty()
        data[0].products.forEach((el) => {
//            console.log(el)

            const stueckpreis = preisFormatter.format(el.price)
            const product = $(`
                    <tr data-id="${el.id}">
                        <td>${el.name} zu je ${stueckpreis}</td>
                        <td><input type="number" class="anzahl" size="3" value=""></td>
                        <td><span class="kosten"></span></td>
                    </tr>`)
            const anzahl = product.find('.anzahl')
            anzahl.val("0")
            const kosten = product.find('.kosten')
            anzahl.change(() => {
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
            /*serviceList.add(`
             <div class="card" style="width: 20rem;">
             <img class="card-img-top" src="${el.teaser}" alt="${el.name}">
             <div class="card-body">
             <h4 class="card-title">${el.name}</h4>
             <p class="card-text">${el.description}</p>
             <a href="#" class="btn btn-primary">${Verwalten}</a>
             </div>
             </div>`)*/
        })
    }
)

function loadOrders() {
    $.get('/api/orders/'+sessionid).then(
        (data) => {
//          console.log("orders", data)
            productsList.find('tr[data-id]').each((idx, el) => {
                const product = $(el)
//            console.log("order tr", el, product)
                const id = product.data('id')
                if (data.broetchen[id]) {
//              console.log("set order", id, data.broetchen[id])
                    product.find('.anzahl').val(data.broetchen[id]).trigger('change')
                }
            })
        }
    )
}

$('#btnBestellen').click(() => {
    orders = {}
    productsList.find('tr[data-id]').each((idx, el) => {
        const product = $(el)
        const id = product.data('id')
        const anzahl = product.find('.anzahl').val()
        orders[id] = anzahl
    })
    const payload = {
        sessionid,
        orders: {
            broetchen: orders
        }
    }
//      console.log("set order", payload)
    $.post('/api/orders', JSON.stringify(payload)).then(
        () => {
            flashSuccess("Es wurde eine E-Mail mit Ihrer Bestellung versendet.")
        })
})


$(function () {
    $('[data-toggle="tooltip"]').tooltip()
})