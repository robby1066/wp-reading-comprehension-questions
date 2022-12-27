class ReadingCompQuestions {
    static setupRcqs() {
        const question_options = document.querySelectorAll('[data-rcq-question-object] LI[data-rcq-question-option]')

        question_options.forEach(option => {
            option.addEventListener('click', (event) => {
                let list = event.target.parentNode
                let options = list.querySelectorAll('li')
                options.forEach(option => {
                    option.classList.remove('selected')
                })

                event.target.classList.add('selected')
            })
        });
    }

}

// attach behaviors
document.addEventListener("DOMContentLoaded", function() {
    ReadingCompQuestions.setupRcqs()
});