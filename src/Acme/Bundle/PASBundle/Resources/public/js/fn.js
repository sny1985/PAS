function GetCurrentPath() {
	var url = window.location.href;
	url = url.slice(0, url.lastIndexOf('/'));
	return url;
}

function ChangeAllCheckboxes() {
	$("#select_all").change(function (){
		$all = $("input[type='checkbox']");
		if ($(this).is(":checked")) {
			$all.prop('checked', true);
		} else {
			$all.prop('checked', false);
		}
	});
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
		$("select option:first", $chair).val("");
		$("select", $chair).prop("required", true);
		$chair.show();
		// clear cfo, president & secretary
		$("select", $cfo).val($("select option:first", $cfo).val());
		$("select", $president).val($("select option:first", $president).val());
		$("select", $secretary).val($("select option:first", $secretary).val());
		if ($unPreApproved.length && !$unPreApproved.prop("checked")) {
			$("select", $chair).prop("required", true);
			$("select", $cfo).val($("select option:last", $cfo).val());
			$cfo.show();
		}
	} else {
		level = 2;
		// change level
		$("#form_level").val($("#form_level option:nth-child(3)").val());
		// clear chair
		$("select option:first", $chair).val('0');
		$("select option:first", $chair).attr("selected", "selected");
		$("select", $chair).val($("select option:first", $chair).val());
		$("select", $chair).prop("required", false);
		if ($unPreApproved.length && !$unPreApproved.prop("checked")) {
			$chair.show();
		}
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
			$.get("currency_rate", {"amount": amount, "curtype": curtype}).done(function(response) {
				$("#usd_amount span").html(response.amount.toFixed(2));
				if (curtype != "USD") {
					$("#usd_amount").show();
				} else {
					$("#usd_amount").hide();
				}

				// decide budget level
				if ($("#form")) {
					SelectLevel(response.amount);
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

	var $bc = $("#form_selectedBudget");

	if (data) {
		$bc.val(data.year + "-" + data.category + "-" + data.holder + "-" + data.amount);
	}

	if ($bc.val()) {
		var value = $bc.val().split("-");
		$("#selected_category").html(value[1]);
		$("#selected_amount").html(value[3]);
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
	var isPreApproved = $("#form_preApproval input[type=radio]:checked").val();
	if (isPreApproved == '1') {
		// clear
		$("#form_budget").val("N/A");
		$("#form_level").val("");
		$("#form_preApprovalNo").prop("required", true);
// 		$invoice.prop("required", true);
		$("#form_chairId select").val(0).removeProp('required');
		// show
		$preApproved.show();
		$("select", $cfo).val($("select option:last", $cfo).val());
		$cfo.show();
	} else if (isPreApproved == '0') {
		$("#form_budget").val("");
        $("#form_chairId").prop("required", true);
		$("#form_preApprovalNo").val("").removeProp("required");
		$("#form_preApprovalNo").val("");
        $("#form_selectedBudget").val("");
// 		$invoice.removeProp("required");
// 		$invoice.replaceWith($invoice.val("").clone(true));
		$unPreApproved.show();
		ConvertCurrency();
	}
}