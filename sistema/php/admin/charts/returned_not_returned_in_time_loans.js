(async function() {
    const data = [
      { state: 'Pasiskolintas inventorius', count: returned_not_returned_in_time_loans[0] },
      { state: 'Laiku grąžintas inventorius', count: returned_not_returned_in_time_loans[1] },
      { state: 'Nelaiku grąžintas inventorius', count: returned_not_returned_in_time_loans[2] },
      { state: 'Dar negrąžintas inventorius', count: returned_not_returned_in_time_loans[3] }
    ];
  
    let title = "Pasiskolintas, grąžintas/negrąžintas inventorius".concat(" ", returned_not_returned_in_time_loans_year, " ", "m.");
  
    new Chart(
      document.getElementById('returned_not_returned_in_time_loans'),
      {
        type: 'bar',
        data: {
          labels: data.map(row => row.state),
          datasets: [
            {
              label: 'Inventorius (vnt.)',
              data: data.map(row => row.count),
              backgroundColor: [
                'rgba(54, 162, 235, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(255, 159, 64, 0.2)',
                'rgba(255, 99, 132, 0.2)'
              ],
              borderColor: [
                'rgb(54, 162, 235)',
                'rgb(75, 192, 192)',
                'rgb(255, 159, 64)',
                'rgb(255, 99, 132)'
              ],
              borderWidth: 1
            }
          ]
        },
        options: {
          indexAxis: 'y',
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
            x: {
                ticks: {
                    callback: function(value, index, ticks) {
                        return value + ' vnt.';
                    },
                    stepSize: 1,
                    font: {
                      size: 16
                    }
                }
            },
            y: {
              ticks: {
                font: {
                    size: 16
                }
              }
            }
          }
        }
      }
    );
  })();