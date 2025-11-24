<?php

namespace FuzeX\PushFilter;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1()
    {
        // Automatically registers the class extension
        $this->createClassExtension('XF\\Repository\\UserPushRepository', 'FuzeX\\PushFilter\\XF\\Repository\\UserPushRepository', 10);
    }

    public function installStep2()
    {
        // Create the option group if it doesn't exist
        $groupFinder = $this->app()->finder('XF:OptionGroup')->where('group_id', 'push_filter_options');
        if ($groupFinder->total() === 0) {
            /** @var \XF\Entity\OptionGroup $group */
            $group = $this->app()->em()->create('XF:OptionGroup');
            $group->group_id = 'push_filter_options';
            $group->display_order = 100;  // Display order in the options menu
            $group->debug_only = false;   // Do not restrict to debug mode
            $group->addon_id = $this->addOn->getAddOnId();
            $group->save();
        }

        // Create the phrase for the option group title
        $groupPhraseTitle = 'option_group.push_filter_options';
        $groupPhraseFinder = $this->app()->finder('XF:Phrase')->where('title', $groupPhraseTitle);
        if ($groupPhraseFinder->total() === 0) {
            /** @var \XF\Entity\Phrase $groupPhrase */
            $groupPhrase = $this->app()->em()->create('XF:Phrase');
            $groupPhrase->title = $groupPhraseTitle;
            $groupPhrase->phrase_text = 'Push Filter Options';
            $groupPhrase->language_id = 0;  // Master language (default)
            $groupPhrase->addon_id = $this->addOn->getAddOnId();
            $groupPhrase->save();
        }

        // Create the textarea option if it doesn't exist
        $optionFinder = $this->app()->finder('XF:Option')->where('option_id', 'push_filter_allowed_hosts');
        if ($optionFinder->total() === 0) {
            /** @var \XF\Entity\Option $option */
            $option = $this->app()->em()->create('XF:Option');
            $option->option_id = 'push_filter_allowed_hosts';
            $option->option_value = "fcm.googleapis.com\nupdates.push.services.mozilla.com\n*.notify.windows.com\n*.push.apple.com";
            $option->edit_format = 'textbox';  // Base type for textarea
            $option->edit_format_params = 'rows=5';  // Define as textarea with 5 rows
            $option->data_type = 'string';
            $option->addon_id = $this->addOn->getAddOnId();
            $option->save();
        }

        // Create the relation to associate the option with the group and define display_order
        $relationFinder = $this->app()->finder('XF:OptionGroupRelation')->where('option_id', 'push_filter_allowed_hosts')->where('group_id', 'push_filter_options');
        if ($relationFinder->total() === 0) {
            /** @var \XF\Entity\OptionGroupRelation $relation */
            $relation = $this->app()->em()->create('XF:OptionGroupRelation');
            $relation->option_id = 'push_filter_allowed_hosts';
            $relation->group_id = 'push_filter_options';
            $relation->display_order = 10;
            $relation->save();
        }

        // Create the phrase for the option title
        $optionPhraseTitle = 'option.push_filter_allowed_hosts';
        $optionPhraseFinder = $this->app()->finder('XF:Phrase')->where('title', $optionPhraseTitle);
        if ($optionPhraseFinder->total() === 0) {
            /** @var \XF\Entity\Phrase $optionPhrase */
            $optionPhrase = $this->app()->em()->create('XF:Phrase');
            $optionPhrase->title = $optionPhraseTitle;
            $optionPhrase->phrase_text = 'Allowed Push Hosts';
            $optionPhrase->language_id = 0;
            $optionPhrase->addon_id = $this->addOn->getAddOnId();
            $optionPhrase->save();
        }

        // Create the phrase for the option description (explanation) (optional, but recommended)
        $optionExplainTitle = 'option_push_filter_allowed_hosts_explain';
        $optionExplainFinder = $this->app()->finder('XF:Phrase')->where('title', $optionExplainTitle);
        if ($optionExplainFinder->total() === 0) {
            /** @var \XF\Entity\Phrase $optionExplain */
            $optionExplain = $this->app()->em()->create('XF:Phrase');
            $optionExplain->title = $optionExplainTitle;
            $optionExplain->phrase_text = 'Enter allowed push notification hosts, one per line. Use wildcards (*) for patterns like *.notify.windows.com.';
            $optionExplain->language_id = 0;
            $optionExplain->addon_id = $this->addOn->getAddOnId();
            $optionExplain->save();
        }
    }

    protected function createClassExtension($fromClass, $toClass, $executeOrder)
    {
        /** @var \XF\Entity\ClassExtension $extension */
        $extension = $this->app()->em()->create('XF:ClassExtension');
        $extension->from_class = $fromClass;
        $extension->to_class = $toClass;
        $extension->execute_order = $executeOrder;
        $extension->active = true;
        $extension->addon_id = $this->addOn->getAddOnId();
        $extension->save();
    }

    public function uninstallStep1()
    {
        // Remove the relation
        $relationFinder = $this->app()->finder('XF:OptionGroupRelation')->where('option_id', 'push_filter_allowed_hosts');
        foreach ($relationFinder->fetch() as $relation) {
            $relation->delete();
        }

        // Remove the option
        $optionFinder = $this->app()->finder('XF:Option')->where('option_id', 'push_filter_allowed_hosts');
        foreach ($optionFinder->fetch() as $option) {
            $option->delete();
        }

        // Remove the group
        $groupFinder = $this->app()->finder('XF:OptionGroup')->where('group_id', 'push_filter_options');
        foreach ($groupFinder->fetch() as $group) {
            $group->delete();
        }

        // Remove associated phrases
        $phrasesToDelete = [
            'option_group.push_filter_options',
            'option.push_filter_allowed_hosts',
            'option_push_filter_allowed_hosts_explain'
        ];
        $phraseFinder = $this->app()->finder('XF:Phrase')->where('title', $phrasesToDelete);
        foreach ($phraseFinder->fetch() as $phrase) {
            $phrase->delete();
        }
    }
}