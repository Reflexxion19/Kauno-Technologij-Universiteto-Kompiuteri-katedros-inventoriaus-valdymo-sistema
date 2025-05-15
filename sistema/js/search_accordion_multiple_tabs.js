document.getElementById("search-box").addEventListener("input", function (e) {
    if (this.value === "") {
        search();
    }
});

function search() {
    input = document.getElementById("search-box");
    filter = input.value.toUpperCase();
    accordion = document.getElementsByClassName("accordion");

    for (i = 0; i < accordion.length; i++) {
        accordionItem = accordion[i].getElementsByClassName("accordion-item");

        for (j = 0; j < accordionItem.length; j++) {
            button = accordionItem[j].querySelector(".accordion-button");
            if (button) {
                txtValue = button.textContent || button.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    accordionItem[j].style.display = "";
                } else {
                    accordionItem[j].style.display = "none";
                }
            }
        }
    }
}