(function ($) {
    'use strict';

    window.sociologInitDecisionTypeSorting = function () {
        const grid = document.getElementById('sortableGrid');
        if (!grid || grid.dataset.sortInitialized === '1') {
            return;
        }

        grid.dataset.sortInitialized = '1';
        let draggedCard = null;
        let orderChanged = false;

        const saveOrder = function () {
            if (!orderChanged) {
                return;
            }

            orderChanged = false;
            const ids = Array.from(grid.querySelectorAll('.type-card'))
                .map(function (card) { return card.dataset.id; });

            $.post(grid.dataset.sortUrl, {
                ids: ids,
                _csrf: grid.dataset.csrf
            });
        };

        grid.querySelectorAll('.type-card').forEach(function (card) {
            card.draggable = true;

            card.addEventListener('dragstart', function (event) {
                draggedCard = card;
                card.classList.add('sortable-placeholder');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', card.dataset.id || '');
            });

            card.addEventListener('dragend', function () {
                card.classList.remove('sortable-placeholder');
                draggedCard = null;
                saveOrder();
            });
        });

        grid.addEventListener('dragover', function (event) {
            if (!draggedCard) {
                return;
            }

            const target = event.target.closest('.type-card');
            if (!target || target === draggedCard || target.parentNode !== grid) {
                return;
            }

            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';

            const targetRect = target.getBoundingClientRect();
            const sameRow = event.clientY >= targetRect.top && event.clientY <= targetRect.bottom;
            const insertAfter = sameRow
                ? event.clientX > targetRect.left + (targetRect.width / 2)
                : event.clientY > targetRect.top + (targetRect.height / 2);

            grid.insertBefore(draggedCard, insertAfter ? target.nextSibling : target);
            orderChanged = true;
        });

        grid.addEventListener('drop', function (event) {
            if (draggedCard) {
                event.preventDefault();
                saveOrder();
            }
        });
    };
})(jQuery);
