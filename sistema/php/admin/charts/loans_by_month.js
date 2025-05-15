(async function() {
  const data = [
    { month: 'Sausis', count: loans_by_month[0] },
    { month: 'Vasaris', count: loans_by_month[1] },
    { month: 'Kovas', count: loans_by_month[2] },
    { month: 'Balandis', count: loans_by_month[3] },
    { month: 'Gegužė', count: loans_by_month[4] },
    { month: 'Birželis', count: loans_by_month[5] },
    { month: 'Liepa', count: loans_by_month[6] },
    { month: 'Rugpjūtis', count: loans_by_month[7] },
    { month: 'Rugsėjis', count: loans_by_month[8] },
    { month: 'Spalis', count: loans_by_month[9] },
    { month: 'Lapkritis', count: loans_by_month[10] },
    { month: 'Gruodis', count: loans_by_month[11] }
  ];

  let title = "Inventoriaus panaudos".concat(" ", loans_by_month_year, " ", "m.");

  new Chart(
    document.getElementById('loans_by_month'),
    {
      type: 'bar',
      data: {
        labels: data.map(row => row.month),
        datasets: [
          {
            label: 'Inventoriaus panaudos (vnt.)',
            data: data.map(row => row.count),
            backgroundColor: [
              'rgba(54, 162, 235, 0.2)'
            ],
            borderColor: [
              'rgb(54, 162, 235)'
            ],
            borderWidth: 1
          }
        ]
      },
      options: {
        plugins: {
          legend: {
            display: false
          },
          title: {
            display: true,
            text: title,
            font: {
              size: 24
            }
          }
        },
        scales: {
          y: {
              ticks: {
                  callback: function(value, index, ticks) {
                      return value + ' vnt.';
                  },
                  stepSize: 1
              }
          }
        }
      }
    }
  );
})();