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
        td = tr[i].getElementsByTagName("td")[0];
        td1 = tr[i].getElementsByTagName("td")[1];

        if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none"; // Užkomentuoti, jeigu norima naudoti antro stulpelio filtravimą bei atkomentuoti sekančią sekciją

                // Patikrinti ar antras stulpelis atitinka filtrą
                // if (td1) {
                //     txtValue = td1.textContent || td1.innerText;
                //     if (txtValue.toUpperCase().indexOf(filter) > -1) {
                //         tr[i].style.display = "";
                //     } else {
                //         tr[i].style.display = "none";
                //     }
                // }
            }
        }
    }
}