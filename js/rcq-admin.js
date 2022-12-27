class ReadingCompQuestions {
    static addNewOption() {
        const template = document.querySelector('TEMPLATE#rcq-option-row')
        const optionsList = document.querySelector('UL#rcq-options')
        const listLength = optionsList.querySelectorAll('li').length

        let newRow = template.content.cloneNode(true)
        newRow.querySelector('INPUT[type=radio]').value = listLength

        optionsList.appendChild(newRow)
    }

    static attachAddNewOptionEvent() {
        const newOptionControls = document.querySelectorAll('A[data-add-new-option-control]')
        newOptionControls.forEach((control) => {
            control.addEventListener('click', (e) => {
                e.preventDefault()
                this.addNewOption()
            })
        })
    }
}

// attach behaviors
document.addEventListener("DOMContentLoaded", function() {
    ReadingCompQuestions.attachAddNewOptionEvent()
});