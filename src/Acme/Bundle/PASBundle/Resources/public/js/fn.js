function SelectYear() {
	// reset
	$(".summary").hide();

	var selected = $("#fiscalyear option:selected").val();
	$("#" + selected).show();
}

function SelectLevel(amount) {
	var $unPreApproved = $("#form_preApproval_1");
	if ($unPreApproved.prop("checked"))
		return;

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
		$("#form_level option:nth-child(3)").prop("selected", false);
		$("#form_level option:nth-child(2)").prop("selected", true);
		$("select", $chair).prop('required', true);
		$chair.show();
		if ($unPreApproved.length && !$unPreApproved.prop("checked")) $cfo.show();
	} else {
		level = 2;
		$("#form_level option:nth-child(2)").prop("selected", false);
		$("#form_level option:nth-child(3)").prop("selected", true);
		$("select", $chair).removeProp('required');
		$cfo.show();
		$president.show();
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
	var $bc = $("#form_budgetCategory");

	if (data) {
		$bc.val(data.year + "-" + data.cat);
	}

	var id = $bc.val();
	if (id) {
		$("#budget_table").show();
		$("#budget_table tbody tr").each(function() {
			if ($(this).prop("id") != id) {
				$(this).hide();
			} else {
				$(this).show();
			}
		});
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
		$("#form_address").prop("required", true);
		$("#form_accountName").val("").removeProp("required");
		$("#form_bankName").val("").removeProp("required");
		$("#form_accountNumber").val("").removeProp("required");
		$("#form_swiftCode").val("").removeProp("required");
		$("#form_routingNumber").val("").removeProp("required");
		$check.show();
	} else if (method == '2') {
		$("#form_companyName").val("").removeProp("required");
		$("#form_attention").val("").removeProp("required");
		$("#form_address").val("").removeProp("required");
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
		$("#form_prid").prop("required", true);
		$invoice.prop("required", true);
		$("#chair select").removeProp('required');
		$preApproved.show();
		$cfo.show();
	} else if (isPreApproved == '0') {
		$("#form_prid").val("").removeProp("required");
		$invoice.removeProp("required");
		$invoice.replaceWith($invoice.val("").clone(true));
		$unPreApproved.show();
		ConvertCurrency();
	}
}