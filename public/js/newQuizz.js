const Quizz = {
    current: {
        questions: {}
    }
};

Quizz.init = function() {
    $('#validQuizz').on('click', function() {
        Quizz.current.id = $('#quizzId').val();

        $('.question').each(function() {
            const $this = $(this);
            const currentId = $this.attr('id');
            Quizz.current.questions[currentId] = {};
            $this.find('.answer').each(function() {
                //May be optimized
                const $this = $(this);
                const answerId = $this.attr('id');
                Quizz.current.questions[currentId][answerId] = $this.is(':checked');
            });
        });

        $.ajax({
            url: '/user/validateQuizz',
            method: 'POST',
            data: {
                quizz: Quizz.current
            }
        });
    });
};

$(document).ready(function () {
    Quizz.init();
})