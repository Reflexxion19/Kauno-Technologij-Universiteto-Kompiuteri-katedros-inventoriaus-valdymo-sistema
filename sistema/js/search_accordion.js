document.getElementById("search-box").addEventListener("input", function (e) {
    if (this.value === "") {
        search();
    }
});

function search() {
    input = document.getElementById("search-box");
    filter = input.value.toUpperCase();
    accordion = document.getElementById("accordion");
    accordionItem = accordion.getElementsByClassName("accordion-item");

    for (i = 0; i < accordionItem.length; i++) {
        button = accordionItem[i].querySelector(".accordion-button");
        if (button) {
            txtValue = button.textContent || button.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                accordionItem[i].style.display = "";
            } else {
                accordionItem[i].style.display = "none";
            }
        }
    }
}