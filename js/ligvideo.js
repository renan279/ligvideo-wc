jQuery(function ($) {
  // Função que lê a tabela do carrinho e monta o JSON de produtos
  function montaProds() {
    var prods = [];
    // Cada linha de item de carrinho
    $('table.shop_table.cart tr.cart_item').each(function () {
      var $tr = $(this);
      var pid = parseInt($tr.data('product_id'));
      var vid = parseInt($tr.data('variation_id')) || null;
      var qty = parseInt($tr.find('input.qty').val()) || 0;
      var id = (vid && vid > 0) ? vid : pid;
      if (qty > 0) {
        prods.push({ id: id, qnt: qty });
      }
    });
    return prods;
  }

  // Quando clicar no botão...
  $('#ligvideo-btn').on('click', function (e) {
    e.preventDefault(); // cancela o link vazio

    var prods = montaProds();
    var base = 'https://cliente.ligvideo.com.br/#/home?id=' + encodeURIComponent(LigVideoConfig.storeId);
    var url = base + '&prod=' + encodeURIComponent(JSON.stringify(prods));

    // Abre a URL em nova aba
    window.open(url, '_blank');
  });
});
