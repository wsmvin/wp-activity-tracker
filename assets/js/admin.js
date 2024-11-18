jQuery(document).ready(function ($) {
    // Initialize datepickers
    $(".datepicker").datepicker({
        dateFormat: "yy-mm-dd",
    });

    // Handle filter form submission
    $("#wat-filters-form").on("submit", function (e) {
        e.preventDefault();

        var url = new URL(window.location.href);

        $(this)
            .find('select, input[type="text"]')
            .each(function () {
                if ($(this).val()) {
                    url.searchParams.set($(this).attr("name"), $(this).val());
                } else {
                    url.searchParams.delete($(this).attr("name"));
                }
            });

        window.location.href = url.toString();
    });

    $(".datepicker").datepicker({
        dateFormat: "yy-mm-dd",
        changeMonth: true,
        changeYear: true,
    });

    $(".wat-table td pre").click(function () {
        $(this).toggleClass("expanded");
    });
    // Toggle details
    $(".wat-details-toggle").click(function () {
        var $content = $(this).siblings(".wat-details-content");
        $(".wat-details-content").not($content).removeClass("active");
        $content.toggleClass("active");
    });

    // Close details when clicking outside
    $(document).click(function (event) {
        if (!$(event.target).closest(".wat-details-wrapper").length) {
            $(".wat-details-content").removeClass("active");
        }
    });

    // Expandable JSON sections
    $(".wat-json").each(function () {
        var $this = $(this);
        var height = $this.height();

        if (height >= 200) {
            $this.after('<div class="wat-json-expand">Show more</div>');
        }
    });

    $(document).on("click", ".wat-json-expand", function () {
        var $json = $(this).prev(".wat-json");
        $json.css("max-height", $json.get(0).scrollHeight + "px");
        $(this)
            .text("Show less")
            .removeClass("wat-json-expand")
            .addClass("wat-json-collapse");
    });

    $(document).on("click", ".wat-json-collapse", function () {
        var $json = $(this).prev(".wat-json");
        $json.css("max-height", "200px");
        $(this)
            .text("Show more")
            .removeClass("wat-json-collapse")
            .addClass("wat-json-expand");
    });
});
