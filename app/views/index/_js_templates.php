<script type="text/template" class="file_template">
<tr data-fileid="<%- id %>">
    <td style="width: 100px"></td>
    <td style="width: 50%">
        <%- name %>
    </td>
    <td style="width: 25%">
        <% if (error) { %>
        <span class="file_error"><?= _('Datei zu groß!') ?></span>
        <% } else { %>
        <progress value="0" max="100" style="width: 100%"></progress>
        <% } %>
    </td>
    <td style="width: 5%">
        <span class="kbs">0</span> kb/s
    </td>
    <td style="width: 10%"><%- size %> kb</td>
    <td style="width: 10%">
        <a href="javascript:STUDIP.epp.removeUploadFile(<%- id %>)">
            <?= Assets::img('icons/16/blue/trash.png') ?>
        </a>
    </td>
</tr>
</script>


<script type="text/template" class="uploaded_file_template">
<tr data-fileid="<%- id %>">
    <td>
        <a href="<%- url %>" target="_blank">
            <%- name %>
        </a>
    </td>
    <td><%- size %> kb</td>
    <td><%- date %></td>
    <td>
        <a href="javascript:STUDIP.epp.removeFile('<%- seminar %>', '<%- id %>')">
            <?= Assets::img('icons/16/blue/trash.png') ?>
        </a>
    </td>
</tr>
</script>

<script type="text/template" class="error_template">
    <?= MessageBox::error('<%- message %>') ?>
</script>


<script type="text/template" class="quest_template">
    <?= createQuestion('<%- question %>', array()) ?>
</script>

<script type="text/template" class="confirm_dialog">
<div class="modaloverlay">
    <div class="messagebox">
        <div class="content">
            <%- question %>
        </div>
        <div class="buttons">
            <a class="accept button" href="<%- confirm %>"><?= _('Ja') ?></a>
            <?= Studip\LinkButton::createCancel(_('Nein'), 'javascript:STUDIP.epp.closeQuestion()') ?>
        </div>
    </div>    
</div>
</script>
