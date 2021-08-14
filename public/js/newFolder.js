const Folder = {
    current: {}
};

Folder.init = function() {
    $('#validFolder').on('click', function() {
        $('.container input').each(function() {
            const $this = $(this);
            if ($this.is(':radio')) {
                Folder.current['sexe'] = $this.val();
            } else {
                Folder.current[$this.attr('id')] = $this.val();
            }

            Folder.current['background'] = $('#background').val();
        });

        $.ajax({
            url: '/user/validateForm',
            method: 'POST',
            data: {
                folder: JSON.stringify(Folder.current)
            },
        }).done(function() {
            window.location.href = '/user/profil';
        });
    });
};

$(document).ready(function () {
    Folder.init();
})