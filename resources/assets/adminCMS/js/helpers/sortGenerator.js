export default function(sortList) {

    return sortList.reduce((memo, item) => {

        let [label, field] = item.split('->');

        memo.push({label: label.trim() + ' ↓', field: field.trim()});
        memo.push({label: label.trim() + ' ↑', field: '-' + field.trim()});

        return memo;

    }, []);

}
