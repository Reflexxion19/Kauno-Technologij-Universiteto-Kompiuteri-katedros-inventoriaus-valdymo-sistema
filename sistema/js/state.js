function saveState(key, state){
    fetch('../../config/state.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: key + '=' + state
    });
}