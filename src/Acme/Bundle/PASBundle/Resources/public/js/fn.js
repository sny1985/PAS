function SelectYear() {
	// reset
	$(".summary").hide();

	var selected = $("#fiscalyear option:selected").val();
	$("#" + selected).show();
}

function SelectLevel(amount) {
	var $unPreApproved = $("#form_preApproval_1");
	if ($unPreApproved.length && $unPreApproved.prop("checked")) {
		return;
	}

	// reset
	var $level = $("#level");
	var $chair = $("#chair");
	$chair.hide();
	var $cfo = $("#cfo");
	$cfo.hide();
	var $president = $("#president");
	$president.hide();
	var $secretary = $("#secretary");
	$secretary.hide();

	var level;
	if (amount <= 10000) {
		level = 1;
		// change level
		$("#form_level").val($("#form_level option:nth-child(2)").val());
		// show chair
//		$("select", $chair).prop("required", true); // HOW TO MAKE IT REQUIRED ???
		$chair.show();
		// clear cfo, president & secretary
		$("select", $cfo).val($("select option:first", $cfo).val());
		$("select", $president).val($("select option:first", $president).val());
		$("select", $secretary).val($("select option:first", $secretary).val());
		if ($unPreApproved.length && !$unPreApproved.prop("checked")) {
			$("select", $cfo).val($("select option:last", $cfo).val());
			$cfo.show();
		}
	} else {
		level = 2;
		// change level
		$("#form_level").val($("#form_level option:nth-child(3)").val());
		// clear chair
		$("select", $chair).val($("select option:first", $chair).val());
//		$("select", $chair).prop("required", false);
		// select first element and show
		$("select", $cfo).val($("select option:last", $cfo).val());
		$cfo.show();
		$("select", $president).val($("select option:last", $president).val());
		$president.show();
		$("select", $secretary).val($("select option:last", $secretary).val());
		$secretary.show();
	}
}

function ConvertCurrency() {
	var $amount = $("#form_amount");
	var $curtype = $("#form_curtype option:selected");
	if ($amount && $curtype) {
		var amount = $amount.val();
		var curtype = $curtype.text();

		// use JSON to get converted currency
		if (amount && curtype) {
			$.get("../../library/currency_converter.php", {"amount": amount, "curtype": curtype})
			.done(function(response) {
				$("#usd_amount span").html(response.usd.toFixed(2));
				if (curtype != "USD") {
					$("#usd_amount").show();
				} else {
					$("#usd_amount").hide();
				}

				// decide budget level
				if ($("#form")) {
					SelectLevel(response.usd);
				}
			});
		}
	}
}

function SelectBudget(data) {
	var $unPreApproved = $("#form_preApproval_1");
	if ($unPreApproved.length && $unPreApproved.prop("checked")) {
		return;
	}

	var $bc = $("#form_budget");

	if (data) {
		$bc.val(data.year + "-" + data.category + "-" + data.amount);
	}

	if ($bc.val()) {
		var value = $bc.val().split("-");
		$("#selected_category").html(value[1]);
		$("#selected_amount").html(value[2]);
		$("#budget_table").show();
	} else {
		$("#budget_table").hide();
	}
}

function SelectPaymentMethod() {
	// reset
	var $check = $("#check");
	$check.hide();
	var $wire = $("#wire");
	$wire.hide();

	// show the correct div(s)
	var method = $("#form_paymentMethod option:selected").val();
	if (method == '1') {
		$("#form_companyName").prop("required", true);
		$("#form_attention").prop("required", true);
		$("#form_street").prop("required", true);
		$("#form_city").prop("required", true);
		$("#form_state").prop("required", true);
		$("#form_zipcode").prop("required", true);
		$("#form_accountName").val("").removeProp("required");
		$("#form_bankName").val("").removeProp("required");
		$("#form_accountNumber").val("").removeProp("required");
		$("#form_swiftCode").val("").removeProp("required");
		$("#form_routingNumber").val("").removeProp("required");
		$check.show();
	} else if (method == '2') {
		$("#form_companyName").val("").removeProp("required");
		$("#form_attention").val("").removeProp("required");
		$("#form_street").val("").removeProp("required");
		$("#form_city").val("").removeProp("required");
		$("#form_state").val("").removeProp("required");
		$("#form_zipcode").val("").removeProp("required");
		$("#form_accountName").prop("required", true);
		$("#form_bankName").prop("required", true);
		$("#form_accountNumber").prop("required", true);
		$("#form_swiftCode").prop("required", true);
		$("#form_routingNumber").prop("required", true);
		$wire.show();
	}
}

function SelectPreApproved() {
	// reset
	var $preApproved = $("#pre_approved");
	$preApproved.hide();
	var $unPreApproved = $("#un_pre_approved");
	$unPreApproved.hide();
	var $cfo = $("#cfo");
	var $invoice = $("#form_invoice");

	// show the correct div(s)
	var isPreApproved = $("input[type=radio]:checked").val();
	if (isPreApproved == '1') {
		// clear
		$("#form_budget").val("N/A");
		$("#form_level").val("");
		$("#form_prid").prop("required", true);
		$invoice.prop("required", true);
		$("#chair select").val(0).removeProp('required');
		// show
		$preApproved.show();
		$("select", $cfo).val($("select option:last", $cfo).val());
		$cfo.show();
	} else if (isPreApproved == '0') {
		$("#form_budget").val("");
		$("#form_prid").val("").removeProp("required");
		$("#form_preApprovalNo").val("");
		$invoice.removeProp("required");
		$invoice.replaceWith($invoice.val("").clone(true));
		$unPreApproved.show();
		ConvertCurrency();
	}
}