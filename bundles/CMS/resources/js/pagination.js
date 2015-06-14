var p = (function(){
  "use strict";

  $(function(){
    $('.pagination:eq(0)').on('click', '#previous:not(.disabled)', p.previousPage)
      .on('click', '#first:not(.disabled)', p.firstPage)
      .next()
      .on('click', 'li:not(.active, #dots, #saut)', p.page)
      .next()
      .on('click', '#next:not(.disabled)', p.nextPage)
      .on('click', '#last:not(.disabled)', p.lastPage);

    $('#sautInput').keypress(p.entry);

    $('#limit').change(p.changeLimit);
  });

  return {
    changes: false,
    offset: 0,
    nbPagesVisibles: 6,
    trs: {},
    overflowCont: $('#paginationOverflowContainer'),
    first: $('#first'),
    prev: $('#previous'),
    pageLis: $('.paginationCenter li'),
    next: $('#next'),
    last: $('#last'),
    dots: $('#dots'),
    indications: $('#indications'),
    limitSel: $('#limit'),
    nbPages: Math.ceil(count / limitSel.val()),

    entry: function(e){
      if(13 === e.which)
      {
        var val = $(this).val();
        if(0 < val && val < (p.nbPages + 1))
          $('.paginationCenter li:eq(' + (val - 1) + ')').trigger('click');
      }
    },

    firstPage: function(){
      var limit = parseInt(p.limitSel.val());

      p.offset = 0;

      p.trs.not('.hidden').addClass('hidden').end()
          .slice(0, limit).removeClass('hidden');

      p.prev.add(p.first).addClass('disabled');
      p.next.add(p.last).removeClass('disabled');

      p.pageLis.filter('.active').removeClass('active').end().eq(0).addClass('active');

      p.indications.text('0 - ' + ((limit > count) ? count : limit) + ' / ' + count);

      $('.paginationCenter').animate({'left': 0}, 500);

      p.dots.addClass('hiddenImp');
    },

    lastPage: function(){
      var limit = parseInt(p.limitSel.val());

      p.offset = (p.nbPages - 1) * limit;

      p.trs.not('.hidden').addClass('hidden').end()
          .slice(p.offset, count).removeClass('hidden');

      p.next.add(p.last).addClass('disabled');
      p.prev.add(p.first).removeClass('disabled');

      p.pageLis.filter('.active').removeClass('active').end().last().addClass('active');

      p.indications.text(count + ' - ' + count + ' / ' + count);

      if(p.nbPages - $(this).index() > p.nbPagesVisibles)
        $('.paginationCenter').animate({'left': -(p.pageLis.eq(p.nbPages - p.nbPagesVisibles).offset().left - p.pageLis.eq(0).offset().left)}, 500);

      p.dots.addClass('hiddenImp');
    },

    previousPage: function(){
      var limit = parseInt(p.limitSel.val());

      p.offset = p.offset - limit;

      p.trs.not('.hidden').addClass('hidden').end()
          .slice(p.offset, p.offset + limit).removeClass('hidden');

      if(p.offset - limit < 0)
        p.first.add(p.prev).addClass('disabled');

      p.next.add(p.last).removeClass('disabled');

      var oldActive = p.pageLis.filter('.active'),
          newActive = p.pageLis.filter('.active').removeClass('active').prev().addClass('active'),
          max = p.offset + limit;

      p.indications.text(p.offset + ' - ' + ((max > count) ? count : max) + ' / ' + count);

      // On bouge les cases pour mettre les cases les plus proches près de la page actuelle
      var $$paginationCenter = $('.paginationCenter'),
          temp = $$paginationCenter.css('left');

      if(!(($$paginationCenter.offset().left - temp.substr(0, temp.length - 2)) - oldActive.offset().left < -2 ))
        $$paginationCenter.animate({'left': '+=' + newActive.width()}, 500);

      p.dots.toggleClass('hiddenImp', newActive.index() + 1 > p.nbPages - p.nbPagesVisibles);
   },

    nextPage: function(){
      var limit = parseInt(p.limitSel.val());

      p.offset = p.offset + limit;

      p.trs.not('.hidden').addClass('hidden').end()
          .slice(p.offset, p.offset + limit).removeClass('hidden');

      if(p.offset + limit >= count)
        p.next.add(last).addClass('disabled');

      p.prev.add(p.first).removeClass('disabled');

      var newActive = p.pageLis.filter('.active').removeClass('active').next().addClass('active'),

      // On bouge les cases pour mettre les cases les plus proches près de la page actuelle
      limiteDroite = Math.ceil(count / limit) - p.nbPagesVisibles,
      index = $(this).index(),
      position = (index > limiteDroite) ? limiteDroite : index;

      // On bouge les cases pour mettre les cases les plus proches près de la page actuelle
      if(newActive.index() < p.nbPages - 5){
        $('.paginationCenter').animate(
          {'left' : '-=' + newActive.width()},
          500
        );
      }

      // Mise à jour de "offset - dernier résultat affiché / nb de résultats"
      var max = p.offset + limit;
      p.indications.text(p.offset + ' - ' + ((max > count) ? count : max) + ' / ' + count);

      p.dots.toggleClass('hiddenImp', newActive.index() + 1 > p.nbPages - p.nbPagesVisibles);
    },

    changeLimit: function(changeTrs){
      var limit = parseInt(p.limitSel.val()),
        lis = '';
        changeTrs = (undefined !== changeTrs ) ? changeTrs : true;

      // Mise à jour de nbPages
      p.nbPages = Math.ceil(count / limit);

      if(changeTrs) {
        p.trs.not('.hidden').addClass('hidden').end()
          .slice(0, limit).removeClass('hidden');
      }

      // Change le css si il y a maintenat moins de 6 pages (dû à une autocomplétion)
      if(p.overflowCont.hasClass('paginationFixedWidth') && 6 > p.nbPages)
        p.overflowCont.removeClass('paginationFixedWidth').addClass('paginationFlexWidth')
          .find('ul').removeClass('paginationCenterStyle')
          .end().nextAll('ul').find('#dots').next().remove().end().remove()
      else if(p.overflowCont.hasClass('paginationFlexWidth') && 6 <= p.nbPages)
        p.overflowCont.removeClass('paginationFlexWidth').addClass('paginationFixedWidth')
          .find('ul').addClass('paginationCenterStyle')
          .end().nextAll('ul').prepend('<li id="dots"><a>...</a></li><li id="saut"><input id="sautInput" placeholder="#" /></li>');

      for(var i=1, nbPages = p.nbPages + 1; i < nbPages; i++)
      {
        lis += (1 === i) ? '<li class="active">' : '<li>';
        lis += '<a>' + i + '</a></li>';
      }

      p.pageLis.remove();
      $('.paginationCenter').html(lis);

      p.next.add(p.last).toggleClass('disabled', (1 === p.nbPages));

      // les lis ont changé, il faut "re-cacher" le bon sélecteur
      p.pageLis = $('.paginationCenter li');

      p.offset = 0;

      // Mise à jour de "offset - dernier résultat affiché / nb de résultats"
      var max = p.offset + limit;
      p.indications.text(p.offset + ' - ' + ((max > count) ? count : max) + ' / ' + count);
    },

    page: function()
    {
      var limit = parseInt(p.limitSel.val()),
        $this = $(this),
        index = $this.index(),
        nextLimit = (index * limit) + limit;

      p.offset = nextLimit - limit;

      // On met à jour le contenu affiché
      p.trs.not('.hidden').addClass('hidden').end()
        .slice(p.offset, nextLimit).removeClass('hidden');

      // On active/désactive les boutons "précédent" et "suivant“ selon la page sélectionnée
      p.prev.add(p.first).toggleClass('disabled', p.offset - limit < 0);
      p.next.add(p.last).toggleClass('disabled', p.offset + limit >= count);

      // On colore la bonne case
      p.pageLis.filter('.active').removeClass('active');
      $this.addClass('active');

      // On bouge les cases pour mettre les cases les plus proches près de la page actuelle
      var paginationCenterSel = $('.paginationCenter'),
          limiteDroite = Math.ceil(count / limit) - p.nbPagesVisibles;

      if(index > limiteDroite)
        paginationCenterSel.animate(
          {'left' : -(p.pageLis.eq(p.nbPages - p.nbPagesVisibles).offset().left - p.pageLis.eq(0).offset().left )},
          500);
      else {
        paginationCenterSel.animate(
          {'left' : -($(this).offset().left - p.pageLis.eq(0).offset().left )},
          500);
      }

      // Mise à jour de "offset - dernier résultat affiché / nb de résultats"
      var max = p.offset + limit;

      p.indications.text(p.offset + ' - ' + ((max > count) ? count : max) + ' / ' + count);

      if(index + 1 > p.nbPages - p.nbPagesVisibles)
        p.dots.addClass('hiddenImp');
    }
  }
}()),
  pagination = p;
