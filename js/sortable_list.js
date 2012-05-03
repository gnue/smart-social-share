function sortable_list(selectors, menu, value_selector, li_class) {
	(function($) {
		$(function() {
			var list = [split($(value_selector).attr('value'), ' '), []];

			// spelator で分割して前後の空白も取除く
			function split(value, spelator) {
				var a = value.split(spelator);
				var result = [];

				$.each(a, function(i, str) {
					str = str.replace(/^\s+|\s+$/g, '');
					if (str != '') result.push(str);
				});

				return result;
			}

			$(selectors.join(',')).sortable({
				connectWith: '.connectedSortable'
			}).disableSelection();

			$(selectors[0]).sortable({
				update: function(event, ui) {
					// データを更新
					var result = $(this).sortable('toArray', {'attribute': 'name'});
					$(value_selector).attr('value', result.join(' '));
				}
			});

			// list1 にない項目のリストを作成
			for (var key in menu) {
				if ($.inArray(key, list[0]) < 0) list[1].push(key);
			}

			for (i = 0; i < 2; i = i + 1) {
				// リストの初期化
				$.each(list[i], function(j, key) {
					var li = $('<li>');

					if (li_class)　li.addClass(li_class);
					li.attr('name', key);
					li.text(menu[key]);

					$(selectors[i]).append(li);
				});
			}
		});

	})(jQuery);
}
