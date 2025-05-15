document.getElementById("search-box").addEventListener("input", function (e) {
    if (this.value === "") {
        search();
    }
});

function search() {
    input = document.getElementById("search-box");
    filter = input.value.toUpperCase();
    table = document.getElementById("table");
    tr = table.getElementsByTagName("tr");

    for (i = 0; i < tr.length; i++) {
        user = tr[i].getElementsByTagName("td")[0];
        select = tr[i].getElementsByTagName("select")[0];

        if (user) {
            txtValue = user.textContent || user.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                if (select) {
                    txtValue = select.options[select.selectedIndex].text;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    }
}